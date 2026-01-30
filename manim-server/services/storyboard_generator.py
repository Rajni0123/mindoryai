"""
Storyboard Generator - Uses AI (Gemini / OpenAI / DeepSeek) to create structured scene storyboards from topics
"""

import os
import json
import re
import time
from config import Config



class StoryboardGenerator:

    # Known working Gemini text generation models (fallback order)
    VALID_TEXT_MODELS = [
        "gemini-2.0-flash",
        "gemini-2.5-flash",
        "gemini-1.5-flash",
        "gemini-1.5-pro",
    ]

    def __init__(self, api_key: str = None, model_name: str = None, storyboard_settings: dict = None):
        self.settings = storyboard_settings or {}
        self.provider = self.settings.get("provider", "gemini")

        if self.provider == "openai":
            self._init_openai()
        elif self.provider == "deepseek":
            self._init_deepseek()
        else:
            self._init_gemini(api_key, model_name)

    def _init_gemini(self, api_key=None, model_name=None):
        """Initialize Gemini client"""
        from google import genai
        self.genai = genai
        from google.genai import types
        self.genai_types = types

        key = api_key or Config.GEMINI_API_KEY
        if not key:
            raise ValueError("GEMINI_API_KEY not provided. Set it in Laravel admin panel or local .env")
        self.client = genai.Client(api_key=key)
        requested = model_name or Config.GEMINI_MODEL
        # Block known-broken models that return 404
        broken_models = ["gemini-nano", "gemini-2.0-flash-exp", "gemini-2.0-flash-thinking-exp"]
        if requested and requested in broken_models:
            self.model_name = self.VALID_TEXT_MODELS[0]
            print(f"[STORYBOARD] Blocked model '{requested}', using fallback: {self.model_name}")
        elif requested and any(requested.startswith(prefix) for prefix in ["gemini-2", "gemini-1"]):
            self.model_name = requested
        else:
            self.model_name = self.VALID_TEXT_MODELS[0]
            print(f"[STORYBOARD] Invalid model '{requested}', using fallback: {self.model_name}")
        print(f"[STORYBOARD] Provider: Gemini, Model: {self.model_name}")

    def _init_openai(self):
        """Initialize OpenAI client"""
        import openai
        key = self.settings.get("openai_key", "")
        if not key:
            raise ValueError("OpenAI API key not provided for storyboard generation")
        self.openai_client = openai.OpenAI(api_key=key)
        self.model_name = self.settings.get("openai_model", "gpt-4o")
        print(f"[STORYBOARD] Provider: OpenAI, Model: {self.model_name}")

    def _init_deepseek(self):
        """Initialize DeepSeek client (OpenAI-compatible API)"""
        import openai
        key = self.settings.get("deepseek_key", "")
        if not key:
            raise ValueError("DeepSeek API key not provided for storyboard generation")
        self.openai_client = openai.OpenAI(
            api_key=key,
            base_url="https://api.deepseek.com"
        )
        self.model_name = self.settings.get("deepseek_model", "deepseek-chat")
        print(f"[STORYBOARD] Provider: DeepSeek, Model: {self.model_name}")

    def clean_text(self, text: str) -> str:
        """Remove markdown, formatting, and banned keywords from text"""
        banned = [
            "diagram", "image", "card", "layout", "slide",
            "[", "]", "{", "}", "<", ">", "*", "#", "|", "```"
        ]

        cleaned = text
        for b in banned:
            cleaned = cleaned.replace(b, "")

        # Remove extra whitespace
        cleaned = " ".join(cleaned.split())
        return cleaned.strip()

    def clean_bullet_points(self, bullets: list) -> list:
        """Clean a list of bullet points"""
        clean = []
        for bullet in bullets:
            if isinstance(bullet, str):
                cleaned = self.clean_text(bullet)
                if len(cleaned) > 2:
                    clean.append(cleaned)
        return clean

    # Language code to full name mapping
    LANGUAGE_NAMES = {
        "en": "English", "hi": "Hindi", "bn": "Bengali", "ta": "Tamil",
        "te": "Telugu", "mr": "Marathi", "gu": "Gujarati", "kn": "Kannada",
        "ml": "Malayalam", "pa": "Punjabi", "ur": "Urdu", "es": "Spanish",
        "fr": "French", "de": "German", "ja": "Japanese", "ko": "Korean",
        "zh": "Chinese", "ar": "Arabic", "pt": "Portuguese", "it": "Italian",
        "ru": "Russian",
    }

    def generate(self, topic: str, max_retries: int = 3, language: str = "en") -> dict:
        """Generate a structured storyboard from a topic/document with retry"""
        prompt = self._build_prompt(topic, language)
        last_error = None

        for attempt in range(1, max_retries + 1):
            try:
                print(f"[STORYBOARD] Attempt {attempt}/{max_retries} for topic: {topic[:60]}...")

                if self.provider == "openai" or self.provider == "deepseek":
                    raw = self._call_openai_compatible(prompt)
                else:
                    raw = self._call_gemini(prompt)

                print(f"[STORYBOARD] Raw response length: {len(raw)}")

                # Strip markdown code blocks if present
                if raw.startswith("```"):
                    raw = re.sub(r"^```(?:json)?\s*", "", raw)
                    raw = re.sub(r"\s*```$", "", raw)
                    raw = raw.strip()

                storyboard = json.loads(raw)

                # Normalize the structure
                storyboard = self._normalize(storyboard)

                self._validate(storyboard)
                self._remove_filler_scenes(storyboard)
                print(f"[STORYBOARD] Success: {len(storyboard['scenes'])} scenes generated")
                return storyboard

            except json.JSONDecodeError as e:
                last_error = f"Invalid JSON from {self.provider}: {e}"
                print(f"[STORYBOARD] JSON parse error on attempt {attempt}: {e}")
            except ValueError as e:
                last_error = str(e)
                print(f"[STORYBOARD] Validation error on attempt {attempt}: {e}")
            except Exception as e:
                last_error = str(e)
                print(f"[STORYBOARD] Error on attempt {attempt}: {e}")

            if attempt < max_retries:
                wait = attempt * 2
                print(f"[STORYBOARD] Retrying in {wait}s...")
                time.sleep(wait)

        raise ValueError(f"Failed after {max_retries} attempts: {last_error}")

    def _call_gemini(self, prompt: str) -> str:
        """Call Gemini API and return raw text response"""
        response = self.client.models.generate_content(
            model=self.model_name,
            contents=prompt,
            config=self.genai_types.GenerateContentConfig(
                temperature=0.7,
                top_k=40,
                top_p=0.95,
                max_output_tokens=8192,
                response_mime_type="application/json",
                safety_settings=[
                    self.genai_types.SafetySetting(category="HARM_CATEGORY_HATE_SPEECH", threshold="OFF"),
                    self.genai_types.SafetySetting(category="HARM_CATEGORY_SEXUALLY_EXPLICIT", threshold="OFF"),
                    self.genai_types.SafetySetting(category="HARM_CATEGORY_DANGEROUS_CONTENT", threshold="OFF"),
                    self.genai_types.SafetySetting(category="HARM_CATEGORY_HARASSMENT", threshold="OFF"),
                ],
            ),
        )
        return response.text.strip()

    def _call_openai_compatible(self, prompt: str) -> str:
        """Call OpenAI or DeepSeek API (both use OpenAI SDK) and return raw text response"""
        response = self.openai_client.chat.completions.create(
            model=self.model_name,
            messages=[
                {
                    "role": "system",
                    "content": "You are an expert educational content creator. You MUST respond with valid JSON only - no markdown, no code blocks, no explanations."
                },
                {
                    "role": "user",
                    "content": prompt
                }
            ],
            temperature=0.7,
            max_tokens=8192,
        )
        return response.choices[0].message.content.strip()

    def _normalize(self, data) -> dict:
        """Normalize various AI response structures into {title, scenes}"""

        # If it's a list, wrap it
        if isinstance(data, list):
            return {"title": "Whiteboard Video", "scenes": data}

        if not isinstance(data, dict):
            raise ValueError(f"Unexpected response type: {type(data).__name__}")

        # Already has scenes at top level
        if isinstance(data.get("scenes"), list):
            return data

        # Nested: {"storyboard": {"scenes": [...]}}
        if isinstance(data.get("storyboard"), dict):
            inner = data["storyboard"]
            if isinstance(inner.get("scenes"), list):
                if "title" not in inner and "title" in data:
                    inner["title"] = data["title"]
                return inner

        # Nested: {"storyboard": [{...}, {...}]}  (scenes directly in storyboard)
        if isinstance(data.get("storyboard"), list):
            return {"title": data.get("title", "Whiteboard Video"), "scenes": data["storyboard"]}

        # Look for any key that contains a list of dicts with narration/visual_description
        for key, value in data.items():
            if isinstance(value, list) and len(value) > 0 and isinstance(value[0], dict):
                if "narration" in value[0] or "visual_description" in value[0]:
                    return {"title": data.get("title", "Whiteboard Video"), "scenes": value}

        # Last resort: check if data itself looks like a single scene
        if "narration" in data and "visual_description" in data:
            return {"title": "Whiteboard Video", "scenes": [data]}

        raise ValueError(f"Cannot find scenes in response. Keys: {list(data.keys())}")

    def _remove_filler_scenes(self, storyboard: dict):
        """Remove intro/welcome/outro filler scenes that add no educational value."""
        filler_words = ["welcome", "introduction", "let's begin", "let's start", "hello", "hi there",
                        "in this video", "today we", "let's explore", "let's learn", "conclusion",
                        "thank you", "thanks for watching", "goodbye", "see you", "that's all"]
        filtered = []
        for scene in storyboard["scenes"]:
            title = (scene.get("elements", {}).get("title_text", "") or scene.get("title", "")).lower()
            narration = scene.get("narration", "").lower()[:80]
            is_filler = any(fw in title or fw in narration for fw in filler_words)
            if not is_filler:
                filtered.append(scene)
        # Keep at least 3 scenes
        if len(filtered) >= 3:
            storyboard["scenes"] = filtered
            # Re-number scenes
            for i, scene in enumerate(storyboard["scenes"]):
                scene["scene_number"] = i + 1

    def _build_prompt(self, topic: str, language: str = "en") -> str:
        lang_name = self.LANGUAGE_NAMES.get(language, "English")
        lang_instruction = ""
        if language == "hi":
            lang_instruction = """
6. CRITICAL LANGUAGE RULE - Use HINGLISH (Hindi + English mix) for ALL content:
   - Write narration in Hinglish - the way an Indian teacher actually speaks to students.
   - Use Hindi sentence structure but freely mix English technical/science terms.
   - Example GOOD narration: "Photosynthesis ek bahut important process hai jo plants mein hota hai. Isme sunlight ki energy use karke CO2 aur water ko glucose mein convert kiya jaata hai."
   - Example BAD narration: "प्रकाश संश्लेषण एक अत्यंत महत्वपूर्ण प्रक्रिया है जो पौधों में होती है।" (too formal/pure Hindi - students won't understand)
   - bullet_points should also be in Hinglish: "Chlorophyll sunlight absorb karta hai aur energy produce karta hai"
   - title_text can use Devanagari script for Hindi words mixed with English: "Photosynthesis kya hai?"
   - key_concepts should be in English (scientific terms): ["Chlorophyll", "Glucose", "Sunlight"]
   - Keep scientific terms, technical words, formulas in English. Use Hindi for explanations.
   - Only JSON keys (like "scenes", "narration") stay in English."""
        elif language != "en":
            lang_instruction = f"""
6. CRITICAL: ALL text content (title, narration, visual_description, title_text, bullet_points, key_concepts) MUST be written in {lang_name} language.
   - The narration MUST be in {lang_name} so the voice reads in {lang_name}.
   - Scene titles and bullet points MUST be in {lang_name}.
   - key_concepts should be short {lang_name} words/phrases.
   - Only JSON keys (like "scenes", "narration") stay in English."""

        return f"""You are an expert educational content creator for MULTI-IMAGE GRID whiteboard animated videos.

Convert the following topic into a structured JSON storyboard. Each scene shows MULTIPLE SMALL images arranged in a GRID on ONE screen, connected by arrows — like a real whiteboard with diagrams.

CRITICAL RULES:
1. Output MUST be valid JSON only - no markdown, no code blocks, no explanations
2. Root object MUST have "scenes" array and "title" string
3. Create 5-7 scenes, each 8-12 seconds long
4. MULTI-IMAGE GRID: each scene shows 2-4 small diagrams on ONE screen, connected by arrows
5. key_concepts MUST have 2-4 items per scene — each becomes ONE small image in the grid
6. narration: 1-2 sentences ONLY — just describe what's on screen, nothing extra
7. bullet_points: one SHORT label per image (3-6 words), same count as key_concepts
8. visual_description: describe the GRID layout and what each small diagram shows
{lang_instruction}

MULTI-IMAGE GRID DESIGN RULES:
- Each key_concept = ONE small focused diagram in the grid (not a complex multi-part image)
- All images appear on the SAME screen simultaneously, connected by arrows
- Think of it like a teacher's whiteboard: multiple small labeled diagrams, arrows showing relationships
- Example GOOD key_concepts for "Newton's Laws":
  ["Ball at rest on table (no motion)", "Hand pushing a box (force applied)", "Rocket launching upward (acceleration)", "F=ma equation with labels"]
- Example BAD key_concepts: ["Newton's first law", "Newton's second law"] (too vague — not drawable)
- Each concept must describe a SINGLE SPECIFIC drawable thing (object, diagram, equation, process step)
- bullet_points are SHORT labels shown below each image: "Object at rest", "Force applied", "Acceleration"
- Keep bullet_points count EQUAL to key_concepts count (one label per image)
- narration is voiceover only — keep it MINIMAL, images teach the concept

REQUIRED JSON STRUCTURE:
{{
  "title": "Video Title",
  "scenes": [
    {{
      "scene_number": 1,
      "title": "Scene Title",
      "narration": "One or two short sentences describing what's on screen",
      "visual_description": "Grid of 2-4 diagrams: top-left shows X, top-right shows Y, connected by arrows",
      "duration": 10,
      "key_concepts": ["Specific drawable diagram 1", "Specific drawable diagram 2", "Specific drawable diagram 3"],
      "elements": {{
        "title_text": "Main heading",
        "bullet_points": [
          "Label for diagram 1",
          "Label for diagram 2",
          "Label for diagram 3"
        ]
      }}
    }}
  ]
}}

LANGUAGE: {"Hinglish (Hindi + English mix, like an Indian teacher speaks)" if language == "hi" else lang_name}
TOPIC: {topic}

Generate the JSON storyboard now. Each scene = multi-image grid with arrows. Keep text MINIMAL, images MAXIMUM{" (use Hinglish - mix Hindi and English naturally, keep science/technical terms in English)" if language == "hi" else f" (ALL content in {lang_name})"}:"""

    def _validate(self, storyboard: dict):
        """Validate storyboard has required structure and clean text"""
        if not isinstance(storyboard.get("scenes"), list):
            raise ValueError("Storyboard must contain a 'scenes' array")

        if len(storyboard["scenes"]) == 0:
            raise ValueError("Storyboard must have at least one scene")

        for i, scene in enumerate(storyboard["scenes"]):
            if "narration" not in scene:
                raise ValueError(f"Scene {i+1} missing 'narration'")
            if "visual_description" not in scene:
                raise ValueError(f"Scene {i+1} missing 'visual_description'")
            if "scene_number" not in scene:
                scene["scene_number"] = i + 1
            if "duration" not in scene or not isinstance(scene["duration"], (int, float)):
                scene["duration"] = 10
            if "elements" not in scene:
                scene["elements"] = {}
            if "key_concepts" not in scene:
                scene["key_concepts"] = []

            # Clean text content
            scene["narration"] = self.clean_text(scene["narration"])
            scene["visual_description"] = self.clean_text(scene["visual_description"])

            # Clean elements
            if "title_text" in scene["elements"]:
                scene["elements"]["title_text"] = self.clean_text(scene["elements"]["title_text"])
            if "bullet_points" in scene["elements"]:
                scene["elements"]["bullet_points"] = self.clean_bullet_points(scene["elements"]["bullet_points"])
