"""
CBSE Previous Year Question Paper Scraper
Scrapes real CBSE board exam questions from educational websites
and uses Gemini AI to structure them into JSON format.

Usage:
    python services/cbse_pyq_scraper.py [--class 10|12|all] [--subject Mathematics] [--year 2024]
"""

import os
import sys
import json
import time
import hashlib
import argparse
import re
import requests
from pathlib import Path
from urllib.parse import quote

# Add parent directory to path for config import
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

try:
    from config import Config
    GEMINI_API_KEY = Config.GEMINI_API_KEY
except ImportError:
    from dotenv import load_dotenv
    load_dotenv()
    GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")

# Try importing optional dependencies
try:
    from bs4 import BeautifulSoup
    HAS_BS4 = True
except ImportError:
    HAS_BS4 = False
    print("[WARN] beautifulsoup4 not installed. Install with: pip install beautifulsoup4")

try:
    import pdfplumber
    HAS_PDFPLUMBER = True
except ImportError:
    HAS_PDFPLUMBER = False
    print("[WARN] pdfplumber not installed. Install with: pip install pdfplumber")


# ============================================================
# CONFIGURATION
# ============================================================

CBSE_CONFIG = {
    "class_10": {
        "exam_slug": "cbse-class-10",
        "display_name": "CBSE Class 10",
        "subjects": ["Mathematics", "Science", "Social Science", "English", "Hindi"],
        "years": [2018, 2019, 2020, 2021, 2022, 2023, 2024],
        "total_marks": 80,
        "duration_minutes": 180,
    },
    "class_12": {
        "exam_slug": "cbse-class-12",
        "display_name": "CBSE Class 12",
        "subjects": ["Physics", "Chemistry", "Mathematics", "Biology", "English"],
        "years": [2018, 2019, 2020, 2021, 2022, 2023, 2024],
        "total_marks": 70,
        "duration_minutes": 180,
    },
}

# Output directory (relative to Laravel project root)
SCRIPT_DIR = Path(os.path.dirname(os.path.abspath(__file__)))
PROJECT_ROOT = SCRIPT_DIR.parent.parent  # Go up from manim-server/services/ to project root
OUTPUT_DIR = PROJECT_ROOT / "storage" / "app" / "cbse-pyq"

GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent"

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "en-US,en;q=0.9,hi;q=0.8",
}

# Known CBSE PYQ source URLs
SOURCES = {
    "class_10": {
        "Mathematics": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-10-maths/",
            "https://mycbseguide.com/cbse-class-10-maths-previous-year-papers/",
        ],
        "Science": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-10-science/",
            "https://mycbseguide.com/cbse-class-10-science-previous-year-papers/",
        ],
        "Social Science": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-10-social-science/",
        ],
        "English": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-10-english/",
        ],
        "Hindi": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-10-hindi/",
        ],
    },
    "class_12": {
        "Physics": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-12-physics/",
            "https://mycbseguide.com/cbse-class-12-physics-previous-year-papers/",
        ],
        "Chemistry": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-12-chemistry/",
        ],
        "Mathematics": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-12-maths/",
        ],
        "Biology": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-12-biology/",
        ],
        "English": [
            "https://www.learncbse.in/cbse-previous-year-question-papers-class-12-english/",
        ],
    },
}


class CBSEPYQScraper:
    """Scrapes CBSE Previous Year Question papers and structures them using Gemini AI."""

    def __init__(self, gemini_api_key: str, output_dir: Path):
        self.api_key = gemini_api_key
        self.output_dir = output_dir
        self.session = requests.Session()
        self.session.headers.update(HEADERS)
        self.stats = {"scraped": 0, "parsed": 0, "failed": 0, "skipped": 0}

    def scrape_all(self, target_class: str = "all", target_subject: str = None, target_year: int = None):
        """Main entry point - scrape all configured CBSE papers."""
        classes = ["class_10", "class_12"] if target_class == "all" else [f"class_{target_class}"]

        for cls in classes:
            if cls not in CBSE_CONFIG:
                print(f"[ERROR] Unknown class: {cls}")
                continue

            config = CBSE_CONFIG[cls]
            subjects = [target_subject] if target_subject else config["subjects"]
            years = [target_year] if target_year else config["years"]

            print(f"\n{'='*60}")
            print(f"  Scraping {config['display_name']}")
            print(f"  Subjects: {', '.join(subjects)}")
            print(f"  Years: {', '.join(map(str, years))}")
            print(f"{'='*60}\n")

            for subject in subjects:
                for year in years:
                    self._process_paper(cls, subject, year, config)

        print(f"\n{'='*60}")
        print(f"  SCRAPING COMPLETE")
        print(f"  Scraped: {self.stats['scraped']}")
        print(f"  Parsed:  {self.stats['parsed']}")
        print(f"  Failed:  {self.stats['failed']}")
        print(f"  Skipped: {self.stats['skipped']}")
        print(f"{'='*60}\n")

    def _process_paper(self, cls: str, subject: str, year: int, config: dict):
        """Process a single paper (subject + year combination)."""
        # Check if already scraped
        output_path = self._get_output_path(cls, subject, year)
        if output_path.exists():
            existing = json.loads(output_path.read_text(encoding="utf-8"))
            if len(existing) >= 10:
                print(f"  [SKIP] {config['display_name']} - {subject} {year} (already has {len(existing)} questions)")
                self.stats["skipped"] += 1
                return

        print(f"  [SCRAPE] {config['display_name']} - {subject} {year}...")

        # Strategy 1: Try scraping from known HTML sources
        raw_text = self._try_scrape_html(cls, subject, year)

        # Strategy 2: If HTML scraping fails, use Gemini's knowledge directly
        if not raw_text or len(raw_text) < 200:
            print(f"    [INFO] HTML scraping insufficient, using Gemini AI knowledge...")
            raw_text = None

        # Parse with Gemini AI
        questions = self._parse_with_gemini(raw_text, subject, year, config)

        if questions and len(questions) >= 5:
            self._save_json(questions, cls, subject, year)
            self.stats["parsed"] += 1
            print(f"    [OK] Saved {len(questions)} questions")
        else:
            self.stats["failed"] += 1
            print(f"    [FAIL] Could not generate enough questions (got {len(questions) if questions else 0})")

        # Rate limiting - be respectful
        time.sleep(2)

    def _try_scrape_html(self, cls: str, subject: str, year: int) -> str:
        """Try to scrape question paper content from HTML sources."""
        if not HAS_BS4:
            return ""

        sources = SOURCES.get(cls, {}).get(subject, [])
        combined_text = ""

        for url in sources:
            try:
                print(f"    [FETCH] {url}")
                resp = self.session.get(url, timeout=15)
                resp.raise_for_status()

                soup = BeautifulSoup(resp.text, "html.parser")

                # Remove scripts, styles, nav, footer
                for tag in soup(["script", "style", "nav", "footer", "header", "aside"]):
                    tag.decompose()

                # Try to find the main content area
                content = soup.find("article") or soup.find("div", class_="entry-content") or soup.find("main")
                if not content:
                    content = soup.find("div", {"id": "content"}) or soup.body

                if content:
                    text = content.get_text(separator="\n", strip=True)

                    # Filter for year-specific content
                    year_patterns = [str(year), f"{year}-{str(year+1)[-2:]}"]
                    lines = text.split("\n")
                    relevant_lines = []
                    in_year_section = False

                    for line in lines:
                        if any(p in line for p in year_patterns):
                            in_year_section = True
                        if in_year_section:
                            relevant_lines.append(line)
                            # Stop at next year section
                            other_years = [str(y) for y in range(2018, 2026) if y != year]
                            if any(f"Year {y}" in line or f"{y} Question" in line for y in other_years):
                                break

                    if relevant_lines:
                        combined_text += "\n".join(relevant_lines) + "\n\n"
                    else:
                        # Take general content if no year-specific section found
                        combined_text += text[:5000] + "\n\n"

                self.stats["scraped"] += 1
                time.sleep(1.5)  # Be respectful

            except Exception as e:
                print(f"    [WARN] Failed to fetch {url}: {e}")

        return combined_text

    def _parse_with_gemini(self, raw_text: str, subject: str, year: int, config: dict) -> list:
        """Use Gemini AI to generate/parse structured questions."""
        if not self.api_key:
            print("    [ERROR] No Gemini API key configured!")
            return []

        class_name = config["display_name"]

        if raw_text and len(raw_text) > 200:
            # We have scraped content - ask Gemini to parse it
            prompt = f"""You are an expert CBSE board exam question paper analyst.

I have scraped the following raw text from a website containing {class_name} {subject} board exam question paper from {year}.

RAW SCRAPED TEXT:
---
{raw_text[:8000]}
---

Based on this text AND your knowledge of actual CBSE {class_name} {subject} {year} board exam paper:

1. Extract/reconstruct ALL questions from the {year} paper
2. Convert every question to MCQ format with 4 options (A, B, C, D)
3. For descriptive/short answer questions, create realistic MCQ versions
4. Provide the correct answer and detailed explanation for each

IMPORTANT:
- These must be REAL questions from the actual CBSE {year} {subject} board exam
- Include questions from ALL sections of the paper
- Generate 25-40 questions covering the full paper
- Assign topics based on NCERT chapter names
- Difficulty: "easy" for Section A (1 mark), "medium" for Section B/C (2-3 marks), "hard" for Section D (5 marks)
- Tag each question with the marks it carried in the original paper

Return ONLY valid JSON:
{{
  "questions": [
    {{
      "question_text": "The exact question from the board exam",
      "topic": "NCERT Chapter/Topic name",
      "options": [
        {{"label": "A", "text": "Option A"}},
        {{"label": "B", "text": "Option B"}},
        {{"label": "C", "text": "Option C"}},
        {{"label": "D", "text": "Option D"}}
      ],
      "correct_answer": "B",
      "explanation": "Detailed step-by-step explanation",
      "difficulty": "medium",
      "marks": 3,
      "section": "B"
    }}
  ]
}}"""
        else:
            # No scraped content - rely on Gemini's knowledge of real CBSE papers
            prompt = f"""You are an expert CBSE board exam question paper setter with complete knowledge of all CBSE board exam papers.

Generate the ACTUAL questions from the **{class_name} {subject} Board Examination {year}** paper.

CRITICAL REQUIREMENTS:
1. These MUST be REAL questions that actually appeared in the CBSE {year} {subject} board exam
2. Include questions from ALL sections:
   - Section A: 1-mark questions (MCQ, fill in blank, very short answer)
   - Section B: 2-mark questions (short answer)
   - Section C: 3-mark questions (short answer)
   - Section D: 5-mark questions (long answer)
3. Convert ALL questions to MCQ format with 4 options each
4. Generate 25-40 questions covering the FULL paper
5. Topics must match NCERT syllabus chapters
6. For {year} specifically:
   - If 2021: This was COVID year with reduced syllabus, include both Term 1 (MCQ) and Term 2 questions
   - If 2020: Pre-COVID full syllabus paper
   - If 2022-2024: Full syllabus papers

SUBJECT DETAILS for {subject}:
- Class: {class_name}
- Total marks: {config['total_marks']}
- Duration: {config['duration_minutes']} minutes

Return ONLY valid JSON:
{{
  "questions": [
    {{
      "question_text": "The exact question from the {year} board exam",
      "topic": "NCERT Chapter/Topic name",
      "options": [
        {{"label": "A", "text": "Option A"}},
        {{"label": "B", "text": "Option B"}},
        {{"label": "C", "text": "Option C"}},
        {{"label": "D", "text": "Option D"}}
      ],
      "correct_answer": "B",
      "explanation": "Detailed step-by-step explanation with NCERT reference",
      "difficulty": "medium",
      "marks": 3,
      "section": "B"
    }}
  ]
}}"""

        # Call Gemini API
        try:
            response = requests.post(
                f"{GEMINI_API_URL}?key={self.api_key}",
                json={
                    "contents": [{"parts": [{"text": prompt}]}],
                    "generationConfig": {
                        "temperature": 0.3,
                        "maxOutputTokens": 8192,
                        "responseMimeType": "application/json",
                    },
                },
                timeout=120,
            )
            response.raise_for_status()
            data = response.json()

            # Extract text from Gemini response
            content = ""
            if "candidates" in data:
                for candidate in data["candidates"]:
                    if "content" in candidate and "parts" in candidate["content"]:
                        for part in candidate["content"]["parts"]:
                            content += part.get("text", "")

            if not content:
                print("    [WARN] Empty response from Gemini")
                return []

            return self._parse_gemini_response(content)

        except requests.exceptions.Timeout:
            print("    [WARN] Gemini API timeout, retrying...")
            time.sleep(5)
            try:
                response = requests.post(
                    f"{GEMINI_API_URL}?key={self.api_key}",
                    json={
                        "contents": [{"parts": [{"text": prompt}]}],
                        "generationConfig": {
                            "temperature": 0.3,
                            "maxOutputTokens": 8192,
                            "responseMimeType": "application/json",
                        },
                    },
                    timeout=180,
                )
                response.raise_for_status()
                data = response.json()
                content = ""
                if "candidates" in data:
                    for candidate in data["candidates"]:
                        if "content" in candidate and "parts" in candidate["content"]:
                            for part in candidate["content"]["parts"]:
                                content += part.get("text", "")
                return self._parse_gemini_response(content) if content else []
            except Exception as e2:
                print(f"    [ERROR] Retry also failed: {e2}")
                return []
        except Exception as e:
            print(f"    [ERROR] Gemini API error: {e}")
            return []

    def _parse_gemini_response(self, content: str) -> list:
        """Parse Gemini's JSON response into a list of question dicts."""
        # Try direct JSON decode
        try:
            data = json.loads(content)
            if isinstance(data, dict) and "questions" in data:
                return self._validate_questions(data["questions"])
            if isinstance(data, list):
                return self._validate_questions(data)
        except json.JSONDecodeError:
            pass

        # Try extracting JSON from markdown code blocks
        match = re.search(r'```(?:json)?\s*(\{[\s\S]*?\})\s*```', content)
        if match:
            try:
                data = json.loads(match.group(1))
                if isinstance(data, dict) and "questions" in data:
                    return self._validate_questions(data["questions"])
            except json.JSONDecodeError:
                pass

        # Try finding JSON object with balanced braces
        match = re.search(r'\{[\s\S]*"questions"[\s\S]*\}', content)
        if match:
            try:
                data = json.loads(match.group(0))
                if "questions" in data:
                    return self._validate_questions(data["questions"])
            except json.JSONDecodeError:
                pass

        print(f"    [WARN] Could not parse Gemini response (length: {len(content)})")
        return []

    def _validate_questions(self, questions: list) -> list:
        """Validate and clean up parsed questions."""
        valid = []
        for q in questions:
            if not isinstance(q, dict):
                continue

            # Must have question text
            q_text = q.get("question_text") or q.get("question") or ""
            if not q_text or len(q_text) < 10:
                continue

            # Must have correct answer
            answer = q.get("correct_answer", "")
            if not answer or answer.upper() not in ["A", "B", "C", "D"]:
                continue

            # Must have options
            options = q.get("options", [])
            if isinstance(options, dict):
                # Convert {A: "...", B: "..."} to [{label: "A", text: "..."}, ...]
                options = [{"label": k, "text": v} for k, v in options.items()]
            if len(options) < 4:
                continue

            # Normalize
            difficulty = q.get("difficulty", "medium").lower()
            if difficulty not in ["easy", "medium", "hard"]:
                difficulty = "medium"

            valid.append({
                "question_text": q_text.strip(),
                "topic": q.get("topic", "General"),
                "options": options[:4],
                "correct_answer": answer.upper().strip(),
                "explanation": q.get("explanation", ""),
                "difficulty": difficulty,
                "marks": q.get("marks", 1),
                "section": q.get("section", "A"),
            })

        return valid

    def _get_output_path(self, cls: str, subject: str, year: int) -> Path:
        """Get the output file path for a given class/subject/year."""
        class_dir = "class10" if cls == "class_10" else "class12"
        dir_path = self.output_dir / class_dir / subject
        dir_path.mkdir(parents=True, exist_ok=True)
        return dir_path / f"{year}.json"

    def _save_json(self, questions: list, cls: str, subject: str, year: int):
        """Save questions to JSON file."""
        output_path = self._get_output_path(cls, subject, year)
        output_path.write_text(
            json.dumps(questions, indent=2, ensure_ascii=False),
            encoding="utf-8"
        )


def main():
    parser = argparse.ArgumentParser(description="CBSE PYQ Scraper")
    parser.add_argument("--class", dest="cls", default="all", help="Class to scrape: 10, 12, or all")
    parser.add_argument("--subject", default=None, help="Specific subject (e.g., Mathematics)")
    parser.add_argument("--year", type=int, default=None, help="Specific year (e.g., 2024)")
    parser.add_argument("--api-key", default=None, help="Gemini API key (overrides env)")
    args = parser.parse_args()

    api_key = args.api_key or GEMINI_API_KEY
    if not api_key:
        print("[ERROR] No Gemini API key found!")
        print("Set GEMINI_API_KEY in .env or pass --api-key")
        sys.exit(1)

    print(f"[INFO] Output directory: {OUTPUT_DIR}")
    print(f"[INFO] Gemini API key: {'*' * 20}...{api_key[-4:]}")

    scraper = CBSEPYQScraper(gemini_api_key=api_key, output_dir=OUTPUT_DIR)
    scraper.scrape_all(
        target_class=args.cls,
        target_subject=args.subject,
        target_year=args.year,
    )


if __name__ == "__main__":
    main()
