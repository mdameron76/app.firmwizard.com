import os
import re
import time
import logging
from dataclasses import dataclass, asdict
from typing import List, Dict, Optional

import requests
from bs4 import BeautifulSoup

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)

GOOGLE_GEOCODE_URL = "https://maps.googleapis.com/maps/api/geocode/json"
GOOGLE_PLACES_TEXT_SEARCH_URL = "https://maps.googleapis.com/maps/api/place/textsearch/json"
GOOGLE_PLACES_DETAILS_URL = "https://maps.googleapis.com/maps/api/place/details/json"


@dataclass
class Competitor:
    name: str
    website: Optional[str]
    practice_areas: List[str]
    address: Optional[str] = None
    phone: Optional[str] = None
    place_id: Optional[str] = None


class LawFirmCompetitorFinder:
    """
    Finds local law firm competitors for a given city/state/zipcode and
    attempts to infer their practice areas from their websites.
    """

    def __init__(
        self,
        google_api_key: str,
        practice_area_keywords: Optional[Dict[str, List[str]]] = None,
        http_timeout: int = 10,
        sleep_between_requests: float = 0.4,
    ):
        self.google_api_key = google_api_key
        self.http_timeout = http_timeout
        self.sleep_between_requests = sleep_between_requests

        # Default practice area keyword mapping (extend as needed)
        self.practice_area_keywords = practice_area_keywords or {
            "Family Law": [
                "family law", "child custody", "paternity", "child support",
                "modification", "guardianship", "adoption", "grandparent rights"
            ],
            "Divorce": [
                "divorce", "dissolution of marriage", "legal separation",
                "spousal support", "alimony", "property division"
            ],
            "Criminal Defense": [
                "criminal defense", "criminal law", "DUI", "DWI", "felony",
                "misdemeanor", "drug charges"
            ],
            "Personal Injury": [
                "personal injury", "car accident", "truck accident",
                "slip and fall", "wrongful death"
            ],
            "Bankruptcy": [
                "bankruptcy", "chapter 7", "chapter 13", "debt relief"
            ],
            "Estate Planning": [
                "estate planning", "wills", "trusts", "probate", "power of attorney"
            ]
        }

    # -------------------- PUBLIC API -------------------- #

    def find_competitors(
        self,
        city: str,
        state: str,
        zipcode: str,
        radius_meters: int = 25000,
        query: str = "law firm"
    ) -> List[Competitor]:
        """
        Main entry point.
        1. Geocode the city/state/zip to lat/lng
        2. Run Google Places Text Search for law firms nearby
        3. For each result, fetch website + infer practice areas
        """
        lat, lng = self._geocode_city_state_zip(city, state, zipcode)
        if lat is None or lng is None:
            logging.error("Failed to geocode location. Aborting.")
            return []

        logging.info(f"Geocoded to lat={lat}, lng={lng}")

        competitors: List[Competitor] = []
        place_results = self._places_text_search(query, lat, lng, radius_meters)

        for place in place_results:
            name = place.get("name")
            place_id = place.get("place_id")
            address = place.get("formatted_address")

            details = self._places_get_details(place_id)
            website = details.get("website")
            phone = details.get("formatted_phone_number")

            practice_areas = []
            if website:
                logging.info(f"Fetching practice areas from {website}")
                practice_areas = self._extract_practice_areas_from_website(website)
                # Be nice to remote servers
                time.sleep(self.sleep_between_requests)

            competitors.append(
                Competitor(
                    name=name,
                    website=website,
                    practice_areas=practice_areas,
                    address=address,
                    phone=phone,
                    place_id=place_id,
                )
            )

        return competitors

    # -------------------- GOOGLE API HELPERS -------------------- #

    def _geocode_city_state_zip(
        self,
        city: str,
        state: str,
        zipcode: str
    ) -> (Optional[float], Optional[float]):
        """
        Uses Google Geocoding API to get lat/lng for a city/state/zip combo.
        """
        address = f"{city}, {state} {zipcode}"
        params = {
            "address": address,
            "key": self.google_api_key
        }
        resp = requests.get(GOOGLE_GEOCODE_URL, params=params, timeout=self.http_timeout)
        data = resp.json()

        if data.get("status") != "OK":
            logging.error(f"Geocoding failed for '{address}': {data.get('status')}")
            return None, None

        result = data["results"][0]
        location = result["geometry"]["location"]
        return location["lat"], location["lng"]

    def _places_text_search(
        self,
        query: str,
        lat: float,
        lng: float,
        radius_meters: int
    ) -> List[dict]:
        """
        Uses Google Places Text Search to find local law firms.
        Automatically handles pagination (next_page_token).
        """
        results = []
        params = {
            "query": query,
            "location": f"{lat},{lng}",
            "radius": radius_meters,
            "key": self.google_api_key
        }

        while True:
            resp = requests.get(
                GOOGLE_PLACES_TEXT_SEARCH_URL,
                params=params,
                timeout=self.http_timeout
            )
            data = resp.json()

            if data.get("status") not in ("OK", "ZERO_RESULTS"):
                logging.error(f"Places Text Search failed: {data.get('status')}")
                break

            results.extend(data.get("results", []))

            next_page_token = data.get("next_page_token")
            if not next_page_token:
                break

            # Google doc: small delay required before next_page_token becomes active
            time.sleep(2)
            params["pagetoken"] = next_page_token

        logging.info(f"Found {len(results)} potential competitors via Places.")
        return results

    def _places_get_details(self, place_id: str) -> dict:
        """
        Uses Google Places Details API to get website, phone, etc.
        """
        params = {
            "place_id": place_id,
            "fields": "name,website,formatted_phone_number",
            "key": self.google_api_key
        }
        resp = requests.get(
            GOOGLE_PLACES_DETAILS_URL,
            params=params,
            timeout=self.http_timeout
        )
        data = resp.json()

        if data.get("status") != "OK":
            logging.warning(f"Places Details failed for {place_id}: {data.get('status')}")
            return {}

        return data["result"]

    # -------------------- PRACTICE AREA EXTRACTION -------------------- #

    def _extract_practice_areas_from_website(self, url: str) -> List[str]:
        """
        Fetches a website and tries to infer practice areas based on keyword mapping.

        Strategy:
        - Fetch HTML
        - Extract:
          - <title>, <meta name="description">, <h1>, <h2>, <h3>, main nav links
        - Match against configured keyword lists
        """
        try:
            resp = requests.get(url, timeout=self.http_timeout, headers={
                "User-Agent": "Mozilla/5.0 (compatible; LocalSEO-Bot/1.0)"
            })
        except Exception as e:
            logging.warning(f"Error fetching {url}: {e}")
            return []

        if resp.status_code >= 400:
            logging.warning(f"Failed to fetch {url}: HTTP {resp.status_code}")
            return []

        soup = BeautifulSoup(resp.text, "html.parser")

        # Collect text from key elements
        texts = []

        # Title
        if soup.title and soup.title.string:
            texts.append(soup.title.string)

        # Meta description
        desc = soup.find("meta", attrs={"name": "description"})
        if desc and desc.get("content"):
            texts.append(desc["content"])

        # Headings
        for tag_name in ["h1", "h2", "h3"]:
            for tag in soup.find_all(tag_name):
                if tag.get_text(strip=True):
                    texts.append(tag.get_text(strip=True))

        # Nav links
        for nav in soup.find_all("nav"):
            for a in nav.find_all("a"):
                if a.get_text(strip=True):
                    texts.append(a.get_text(strip=True))

        # Fallback: some body text
        body_snippet = soup.get_text(separator=" ", strip=True)[:5000]
        texts.append(body_snippet)

        full_text = " ".join(texts).lower()

        detected_practice_areas = set()

        for practice_area, keywords in self.practice_area_keywords.items():
            for kw in keywords:
                pattern = r"\b" + re.escape(kw.lower()) + r"\b"
                if re.search(pattern, full_text):
                    detected_practice_areas.add(practice_area)
                    break  # don't double-count same area

        return sorted(detected_practice_areas)


# -------------------- Example usage -------------------- #

if __name__ == "__main__":
    # Load API key from environment variable for safety
    GOOGLE_API_KEY = os.getenv("GOOGLE_MAPS_API_KEY", "YOUR_API_KEY_HERE")

    finder = LawFirmCompetitorFinder(google_api_key=GOOGLE_API_KEY)

    # Example: Find law firm competitors in Festus, MO 63028
    competitors = finder.find_competitors(
        city="Festus",
        state="MO",
        zipcode="63028",
        radius_meters=25000,  # ~25km
        query="law firm"
    )

    for comp in competitors:
        print(asdict(comp))
