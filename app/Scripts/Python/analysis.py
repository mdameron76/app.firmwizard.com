import re
from collections import Counter
from typing import Dict, Tuple, Any

import requests
from bs4 import BeautifulSoup


STOPWORDS = {
    "the",
    "and",
    "for",
    "of",
    "a",
    "to",
    "in",
    "on",
    "at",
    "with",
    "is",
    "are",
    "your",
    "our",
    "you",
}


def fetch_html(url: str, timeout: int = 15) -> str:
    headers = {"User-Agent": "Mozilla/5.0 (compatible; SEOAnalyzer/1.0)"}
    resp = requests.get(url, headers=headers, timeout=timeout)
    resp.raise_for_status()
    return resp.text


def parse_dom(html: str) -> Dict[str, Any]:
    soup = BeautifulSoup(html, "html.parser")

    title = (soup.title.string or "").strip() if soup.title else ""

    meta_desc = ""
    meta_tag = soup.find("meta", attrs={"name": "description"})
    if meta_tag and meta_tag.get("content"):
        meta_desc = meta_tag["content"].strip()

    # Headings
    headings = {}
    for level in range(1, 7):
        tag = f"h{level}"
        headings[tag] = [h.get_text(" ", strip=True) for h in soup.find_all(tag)]

    # remove non-content tags for body extraction
    for tag in soup(["script", "style", "noscript", "svg"]):
        tag.decompose()

    # crude body text extraction
    body_text = " ".join([p.get_text(" ", strip=True) for p in soup.find_all(["p", "div"])])
    if not body_text:
        # fallback to whole-text
        body_text = soup.get_text(" ", strip=True)

    return {
        "title": title,
        "meta_description": meta_desc,
        "headings": headings,
        "body_text": body_text,
        "soup": soup,
    }


def tokenize(text: str):
    tokens = re.findall(r"[a-zA-Z]+", (text or "").lower())
    return [t for t in tokens if t not in STOPWORDS and len(t) > 2]


def count_keywords(text: str) -> Counter:
    tokens = tokenize(text)
    return Counter(tokens)


def ngrams(tokens, n=2):
    return [" ".join(tokens[i : i + n]) for i in range(len(tokens) - n + 1)]


def count_ngrams(text: str, n=2) -> Counter:
    tokens = tokenize(text)
    return Counter(ngrams(tokens, n))


def ngram_positions(text: str, n=2) -> Tuple[Dict[str, int], int]:
    """Get first position of each n-gram in the tokenized text."""
    tokens = tokenize(text)
    ng = ngrams(tokens, n)
    positions = {}
    for i, gram in enumerate(ng):
        if gram not in positions:
            positions[gram] = i
    return positions, len(tokens)


def add_placement_info(keywords_data: Dict[str, Dict], text: str) -> Dict[str, Dict]:
    tokens = tokenize(text)
    positions = {}
    for i, tok in enumerate(tokens):
        if tok not in positions:
            positions[tok] = i
    return positions, len(tokens)


def add_placement_info(keywords_data: Dict[str, Dict], text: str) -> Dict[str, Dict]:
    positions, total = keyword_positions(text)
    for kw, info in keywords_data.items():
        pos = positions.get(kw)
        if pos is None:
            info["first_position"] = None
            info["first_position_percent"] = None
        else:
            info["first_position"] = pos
            info["first_position_percent"] = pos / max(total, 1)
    return keywords_data


def add_ngram_placement_info(ngram_data: Dict[str, Dict], text: str, n: int) -> Dict[str, Dict]:
    """Add placement info for n-grams (bigrams, trigrams, etc.)."""
    positions, total = ngram_positions(text, n)
    for gram, info in ngram_data.items():
        pos = positions.get(gram)
        if pos is None:
            info["first_position"] = None
            info["first_position_percent"] = None
        else:
            info["first_position"] = pos
            info["first_position_percent"] = pos / max(total, 1)
    return ngram_data


def keyword_positions(text: str) -> Tuple[Dict[str, int], int]:
    tokens = tokenize(text)
    positions = {}
    for i, tok in enumerate(tokens):
        if tok not in positions:
            positions[tok] = i
    return positions, len(tokens)


def analyze_keywords(regions: Dict[str, Any]) -> Dict[str, Any]:
    overall_tokens = tokenize(regions.get("body_text", ""))
    total_words = len(overall_tokens) if overall_tokens else 1

    data = {}

    overall_counts = count_keywords(regions.get("body_text", ""))

    title_counts = count_keywords(regions.get("title", ""))
    meta_counts = count_keywords(regions.get("meta_description", ""))
    h_counts = {level: count_keywords(" ".join(texts)) for level, texts in regions.get("headings", {}).items()}

    # Bigrams analysis
    bigrams_overall = count_ngrams(regions.get("body_text", ""), 2)
    bigrams_title = count_ngrams(regions.get("title", ""), 2)
    bigrams_meta = count_ngrams(regions.get("meta_description", ""), 2)
    bigrams_h_counts = {level: count_ngrams(" ".join(texts), 2) for level, texts in regions.get("headings", {}).items()}

    # Trigrams analysis
    trigrams_overall = count_ngrams(regions.get("body_text", ""), 3)
    trigrams_title = count_ngrams(regions.get("title", ""), 3)
    trigrams_meta = count_ngrams(regions.get("meta_description", ""), 3)
    trigrams_h_counts = {level: count_ngrams(" ".join(texts), 3) for level, texts in regions.get("headings", {}).items()}

    for kw, freq in overall_counts.items():
        info = {
            "frequency": freq,
            "density": freq / total_words,
            "in_title": kw in title_counts,
            "in_meta_description": kw in meta_counts,
        }
        for level in h_counts:
            info[f"in_{level}"] = kw in h_counts.get(level, {})

        data[kw] = info

    # Build bigram data with placement info
    bigram_data = {}
    for gram, freq in bigrams_overall.items():
        info = {
            "frequency": freq,
            "density": freq / total_words,
            "in_title": gram in bigrams_title,
            "in_meta_description": gram in bigrams_meta,
        }
        for level in bigrams_h_counts:
            info[f"in_{level}"] = gram in bigrams_h_counts.get(level, {})
        bigram_data[gram] = info

    # Build trigram data with placement info
    trigram_data = {}
    for gram, freq in trigrams_overall.items():
        info = {
            "frequency": freq,
            "density": freq / total_words,
            "in_title": gram in trigrams_title,
            "in_meta_description": gram in trigrams_meta,
        }
        for level in trigrams_h_counts:
            info[f"in_{level}"] = gram in trigrams_h_counts.get(level, {})
        trigram_data[gram] = info

    return {
        "keywords": data,
        "bigrams": bigram_data,
        "trigrams": trigram_data,
        "summary": {"total_words": total_words, "unique_keywords": len(data)},
    }
