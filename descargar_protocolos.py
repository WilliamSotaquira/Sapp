import csv
import os
import re
import time
import unicodedata
from collections import deque
from urllib.parse import urljoin, urlparse, unquote

import requests
import urllib3
from bs4 import BeautifulSoup
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

START_URL = "https://portalwebapp.movilidadbogota.gov.co/web/protocolos"
OUT_DIR = "protocolos_descargas"
CSV_REPORT = "reporte_descargas.csv"
TIMEOUT = 30
SLEEP_BETWEEN_REQUESTS = 0.2
ALLOWED_EXT = {".pdf", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".zip", ".rar", ".csv"}
DOMAIN = urlparse(START_URL).netloc
VERIFY_SSL = False

os.makedirs(OUT_DIR, exist_ok=True)
if not VERIFY_SSL:
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def build_session() -> requests.Session:
    s = requests.Session()
    s.headers.update(
        {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/122.0 Safari/537.36"
        }
    )
    retry = Retry(
        total=5,
        connect=5,
        read=5,
        backoff_factor=1.0,
        status_forcelist=[429, 500, 502, 503, 504],
        allowed_methods=["GET", "HEAD"],
        raise_on_status=False,
    )
    adapter = HTTPAdapter(max_retries=retry)
    s.mount("http://", adapter)
    s.mount("https://", adapter)
    s.verify = VERIFY_SSL
    return s


def is_internal(url: str) -> bool:
    p = urlparse(url)
    return p.netloc in ("", DOMAIN)


def has_download_ext(url: str) -> bool:
    path = urlparse(url).path.lower().split("?")[0]
    return any(path.endswith(ext) for ext in ALLOWED_EXT)


def safe_filename(url: str, fallback_index: int) -> str:
    p = urlparse(url).path
    raw_name = os.path.basename(p) or f"archivo_{fallback_index}"
    decoded = unquote(raw_name)

    # Remove accents and keep only ASCII-safe chars.
    normalized = unicodedata.normalize("NFKD", decoded)
    ascii_name = normalized.encode("ascii", "ignore").decode("ascii")
    ascii_name = re.sub(r"[^\w.\- ]+", "_", ascii_name)
    ascii_name = re.sub(r"\s+", "_", ascii_name)
    ascii_name = re.sub(r"_+", "_", ascii_name).strip("._ ")

    if not ascii_name:
        ascii_name = f"archivo_{fallback_index}"
    if not os.path.splitext(ascii_name)[1]:
        ascii_name = f"{ascii_name}.bin"
    return ascii_name


def load_previous_report(csv_path: str):
    by_url = {}
    if not os.path.exists(csv_path):
        return by_url
    with open(csv_path, "r", newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            by_url[row["url"]] = row
    return by_url


def crawl_file_links(session: requests.Session, start_url: str):
    visited_pages = set()
    file_urls = set()
    queue = deque([start_url])

    while queue:
        page_url = queue.popleft()
        parsed_page = urlparse(page_url)
        if parsed_page.scheme and parsed_page.scheme not in ("http", "https"):
            continue
        if page_url in visited_pages:
            continue
        visited_pages.add(page_url)

        try:
            r = session.get(page_url, timeout=TIMEOUT)
            ctype = (r.headers.get("Content-Type") or "").lower()

            if r.status_code >= 400:
                continue

            if "text/html" not in ctype and has_download_ext(page_url):
                file_urls.add(page_url)
                continue

            soup = BeautifulSoup(r.text, "html.parser")
            for a in soup.select("a[href]"):
                href = (a.get("href") or "").strip()
                if not href:
                    continue
                href_lower = href.lower()
                if href_lower.startswith(("mailto:", "tel:", "javascript:", "#")):
                    continue
                full = urljoin(page_url, href)

                if not is_internal(full):
                    continue

                if has_download_ext(full):
                    file_urls.add(full)
                else:
                    if full not in visited_pages:
                        queue.append(full)

            time.sleep(SLEEP_BETWEEN_REQUESTS)
        except Exception as e:
            print(f"[WARN] No se pudo leer página: {page_url} -> {e}")
            continue

    return sorted(file_urls)


def main():
    session = build_session()
    previous = load_previous_report(CSV_REPORT)
    file_urls = crawl_file_links(session, START_URL)

    print(f"Archivos encontrados: {len(file_urls)}")

    rows = []
    for i, url in enumerate(file_urls, 1):
        prev = previous.get(url)

        if prev and prev.get("estado") in ("OK", "SKIP") and os.path.exists(prev.get("archivo_local", "")):
            rows.append(
                {
                    "url": url,
                    "archivo_local": prev["archivo_local"],
                    "estado": "SKIP",
                    "mensaje": "Ya descargado anteriormente",
                }
            )
            print(f"[SKIP] {url}")
            continue

        try:
            rr = session.get(url, timeout=TIMEOUT, stream=True)
            if rr.status_code >= 400:
                rows.append(
                    {
                        "url": url,
                        "archivo_local": "",
                        "estado": "FAIL",
                        "mensaje": f"HTTP {rr.status_code}",
                    }
                )
                print(f"[FAIL] {url} -> HTTP {rr.status_code}")
                continue

            filename = safe_filename(url, i)
            out_path = os.path.join(OUT_DIR, filename)

            if os.path.exists(out_path):
                base, ext = os.path.splitext(out_path)
                n = 1
                while os.path.exists(f"{base}_{n}{ext}"):
                    n += 1
                out_path = f"{base}_{n}{ext}"

            with open(out_path, "wb") as f:
                for chunk in rr.iter_content(chunk_size=1024 * 128):
                    if chunk:
                        f.write(chunk)

            rows.append(
                {
                    "url": url,
                    "archivo_local": os.path.abspath(out_path),
                    "estado": "OK",
                    "mensaje": "Descargado",
                }
            )
            print(f"[OK] {filename}")

            time.sleep(SLEEP_BETWEEN_REQUESTS)

        except Exception as e:
            rows.append(
                {
                    "url": url,
                    "archivo_local": "",
                    "estado": "FAIL",
                    "mensaje": str(e),
                }
            )
            print(f"[FAIL] {url} -> {e}")

    merged = {}
    for u, row in previous.items():
        merged[u] = row
    for row in rows:
        merged[row["url"]] = row

    with open(CSV_REPORT, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=["url", "archivo_local", "estado", "mensaje"])
        writer.writeheader()
        for row in merged.values():
            writer.writerow(row)

    ok = sum(1 for r in rows if r["estado"] == "OK")
    skip = sum(1 for r in rows if r["estado"] == "SKIP")
    fail = sum(1 for r in rows if r["estado"] == "FAIL")
    print(f"\nResumen: OK={ok} | SKIP={skip} | FAIL={fail}")
    print(f"Reporte: {os.path.abspath(CSV_REPORT)}")


if __name__ == "__main__":
    main()
