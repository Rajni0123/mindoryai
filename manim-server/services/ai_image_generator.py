"""
AI Image Generator - Multi-provider whiteboard illustration generator.
Supports: Google Imagen, OpenAI DALL-E 3, OpenAI GPT-Image-1, Stability AI.
Provider is selected from Laravel admin settings.

FALLBACK CHAIN: If primary provider fails, try configured fallback provider.

IMAGE STYLE: Black outline drawings on white background (like textbook diagrams)
"""

import os
import time
import requests
import traceback
import concurrent.futures
from config import Config


class AIImageGenerator:
    """Generates whiteboard outline illustrations using configurable AI providers"""

    # Google Imagen model
    IMAGEN_MODEL = "imagen-4.0-fast-generate-001"

    # Provider display names for logging
    PROVIDER_NAMES = {
        "gemini_imagen": "Google Imagen",
        "openai_dalle": "OpenAI DALL-E 3",
        "openai_gpt_image": "OpenAI GPT-Image-1",
        "stability_ai": "Stability AI",
    }

    def __init__(self, api_key: str = None, image_settings: dict = None):
        self.settings = image_settings or {}
        self.enabled = self.settings.get("enabled", True)
        self.provider = self.settings.get("provider", "gemini_imagen")
        self.provider_api_key = self.settings.get("api_key") or ""

        # Fallback provider
        self.fallback_provider = self.settings.get("fallback_provider", "")

        # Gemini/Imagen API key (fallback to main gemini key)
        self.gemini_key = api_key or Config.GEMINI_API_KEY

        # Rate limit tracking (per-instance)
        self._imagen_exhausted = False
        self._imagen_exhausted_time = 0
        self._imagen_cooldown = 15  # seconds (reduced from 60)
        self._request_count = 0
        import threading
        self._lock = threading.Lock()

        # Initialize provider-specific client
        self._gemini_client = None
        if self.provider == "gemini_imagen" and self.enabled:
            key = self.provider_api_key or self.gemini_key
            if key:
                try:
                    from google import genai
                    self._gemini_client = genai.Client(api_key=key)
                except Exception as e:
                    print(f"[AI-IMG] Failed to init Gemini client: {e}")

    def generate_scene_image(self, title: str, description: str, concepts: list, output_path: str) -> str:
        """Generate a whiteboard-style educational diagram for a scene."""
        if not self.enabled:
            print("[AI-IMG] Image generation disabled in settings")
            return None

        prompt = (
            f"Draw ONE single educational diagram about: {title}. "
            f"{description[:200]}. "
            f"Label ONLY '{title}' with an arrow pointing to the main part. "
            "STRICT RULES: "
            "1. PURE WHITE background - NO colored backgrounds, NO boxes, NO borders, NO frames, NO containers. "
            "2. Draw ONLY ONE focused diagram with ONE label and ONE arrow pointing to the key part. "
            "3. Use a directional ARROW with a highlighted label showing the concept name. "
            "4. BLACK OUTLINE ONLY - simple clean line drawing like a textbook diagram. "
            "5. NO colors, NO shading, NO gradients - only black lines and text on white. "
            "6. NO border, NO frame, NO box around the image. Clean edges that blend into white. "
            "7. Landscape orientation. No photo-realistic, no 3D."
        )
        return self._generate(prompt, output_path, title)

    def generate_concept_image(self, concept: str, scene_title: str, description: str, output_path: str) -> str:
        """Generate a UNIQUE visual for ONE specific concept."""
        if not self.enabled:
            return None

        prompt = (
            f"Draw ONE specific educational diagram of '{concept}'. "
            f"Show ONLY '{concept}' as a clear illustration. "
            f"Add ONE label saying '{concept}' with an arrow pointing to the main part and a yellow highlighter effect on the label. "
            "STRICT RULES: "
            "1. PURE WHITE background - NO colored backgrounds, NO boxes, NO borders, NO frames around the image. "
            "2. ONLY this ONE specific concept - NOT the full topic, NOT multiple concepts. "
            "3. ONE arrow pointing from the label to the key part of the diagram. "
            "4. BLACK OUTLINE drawing style - simple clean lines like a textbook diagram. "
            "5. NO colors except the yellow highlight on the label. NO shading, NO gradients. "
            "6. NO border, NO frame, NO box around the image. Clean edges blending into white. "
            "7. Landscape. No photo-realistic, no 3D."
        )
        return self._generate(prompt, output_path, concept)

    def _generate(self, prompt: str, output_path: str, label: str) -> str:
        """Route to the selected provider with automatic fallback."""
        os.makedirs(os.path.dirname(output_path), exist_ok=True)

        provider_map = {
            "gemini_imagen": self._generate_gemini_imagen,
            "openai_dalle": self._generate_openai_dalle,
            "openai_gpt_image": self._generate_openai_gpt_image,
            "stability_ai": self._generate_stability_ai,
        }

        # Try primary provider
        primary_name = self.PROVIDER_NAMES.get(self.provider, self.provider)
        print(f"[AI-IMG] Primary provider: {primary_name}")

        generator = provider_map.get(self.provider, self._generate_gemini_imagen)
        result = generator(prompt, output_path, label)

        # If primary succeeded
        if result:
            self._try_remove_background(result)
            return result

        # Primary failed - try fallback if different from primary
        if self.fallback_provider and self.fallback_provider != self.provider:
            fallback_name = self.PROVIDER_NAMES.get(self.fallback_provider, self.fallback_provider)
            print(f"[AI-IMG] Primary failed, trying fallback: {fallback_name}")

            fallback_generator = provider_map.get(self.fallback_provider)
            if fallback_generator:
                result = fallback_generator(prompt, output_path, label)
                if result:
                    print(f"[AI-IMG] Fallback {fallback_name} succeeded!")
                    self._try_remove_background(result)
                    return result
                else:
                    print(f"[AI-IMG] Fallback {fallback_name} also failed")

        print(f"[AI-IMG] ALL providers FAILED for: {label[:50]}")
        return None

    # =========================================================================
    # GOOGLE IMAGEN (via Gemini API)
    # =========================================================================
    def _generate_gemini_imagen(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using Google Imagen with rate limit handling."""
        if not self._gemini_client:
            print("[AI-IMG] Gemini client not initialized")
            return None

        # Check if we're in cooldown from a previous 429
        if self._imagen_exhausted:
            elapsed = time.time() - self._imagen_exhausted_time
            if elapsed < self._imagen_cooldown:
                remaining = int(self._imagen_cooldown - elapsed)
                print(f"[AI-IMG] Imagen in cooldown ({remaining}s left), skipping to fallback")
                return None
            # Cooldown expired, reset
            print(f"[AI-IMG] Imagen cooldown expired, retrying")
            self._imagen_exhausted = False

        # Thread-safe request counting with minimal delay
        with self._lock:
            self._request_count += 1
            count = self._request_count
        if count > 2:  # Only delay after first 2 concurrent requests
            delay = 1
            print(f"[AI-IMG] Rate limit prevention: waiting {delay}s...")
            time.sleep(delay)

        return self._try_imagen(prompt, output_path, label)

    def _try_imagen(self, prompt: str, output_path: str, label: str) -> str:
        """Try Imagen - single attempt with 90s timeout, fail fast on 429."""
        model = self.IMAGEN_MODEL

        try:
            from google.genai import types
            print(f"[AI-IMG] Trying {model}...")

            # Use thread pool to enforce 90s timeout on API call
            def _call_imagen():
                return self._gemini_client.models.generate_images(
                    model=model,
                    prompt=prompt,
                    config=types.GenerateImagesConfig(
                        number_of_images=1,
                        output_mime_type="image/png",
                    ),
                )

            with concurrent.futures.ThreadPoolExecutor(max_workers=1) as pool:
                future = pool.submit(_call_imagen)
                response = future.result(timeout=90)

            if response.generated_images:
                with open(output_path, "wb") as f:
                    f.write(response.generated_images[0].image.image_bytes)
                print(f"[AI-IMG] OK ({model}): {output_path}")
                return output_path
            else:
                print(f"[AI-IMG] {model}: No images in response")
        except concurrent.futures.TimeoutError:
            print(f"[AI-IMG] {model} TIMED OUT after 90s, marking exhausted")
            self._imagen_exhausted = True
            self._imagen_exhausted_time = time.time()
        except Exception as e:
            err_str = str(e)
            print(f"[AI-IMG] {model} error: {err_str[:200]}")

            if "429" in err_str or "RESOURCE_EXHAUSTED" in err_str:
                self._imagen_exhausted = True
                self._imagen_exhausted_time = time.time()
                print(f"[AI-IMG] Imagen quota exhausted, cooldown {self._imagen_cooldown}s, falling back immediately")
            elif "404" in err_str or "NOT_FOUND" in err_str:
                print(f"[AI-IMG] Model {model} not available")

        return None

    # =========================================================================
    # OPENAI DALL-E 3
    # =========================================================================
    def _generate_openai_dalle(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using OpenAI DALL-E 3 API."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] OpenAI API key not configured")
            return None

        try:
            print(f"[AI-IMG] Trying DALL-E 3 for: {label[:40]}...")
            response = requests.post(
                "https://api.openai.com/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": "dall-e-3",
                    "prompt": prompt,
                    "n": 1,
                    "size": "1792x1024",
                    "quality": "standard",
                    "response_format": "url",
                },
                timeout=60,
            )

            if response.status_code == 200:
                data = response.json()
                image_url = data["data"][0]["url"]
                img_response = requests.get(image_url, timeout=30)
                if img_response.status_code == 200:
                    with open(output_path, "wb") as f:
                        f.write(img_response.content)
                    print(f"[AI-IMG] OK (dall-e-3): {output_path}")
                    return output_path
            else:
                err = response.json().get("error", {}).get("message", response.text[:200])
                print(f"[AI-IMG] DALL-E 3 error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] DALL-E 3 exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # OPENAI GPT-IMAGE-1
    # =========================================================================
    def _generate_openai_gpt_image(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using OpenAI gpt-image-1 model."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] OpenAI API key not configured for gpt-image-1")
            return None

        try:
            print(f"[AI-IMG] Trying gpt-image-1 for: {label[:40]}...")
            response = requests.post(
                "https://api.openai.com/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": "gpt-image-1",
                    "prompt": prompt,
                    "n": 1,
                    "size": "1536x1024",
                    "quality": "low",
                },
                timeout=90,
            )

            if response.status_code == 200:
                data = response.json()
                image_data = data["data"][0]
                if "b64_json" in image_data:
                    import base64
                    img_bytes = base64.b64decode(image_data["b64_json"])
                    with open(output_path, "wb") as f:
                        f.write(img_bytes)
                elif "url" in image_data:
                    img_response = requests.get(image_data["url"], timeout=30)
                    if img_response.status_code == 200:
                        with open(output_path, "wb") as f:
                            f.write(img_response.content)
                    else:
                        print(f"[AI-IMG] gpt-image-1 download failed: {img_response.status_code}")
                        return None
                print(f"[AI-IMG] OK (gpt-image-1): {output_path}")
                return output_path
            else:
                err = response.json().get("error", {}).get("message", response.text[:200])
                print(f"[AI-IMG] gpt-image-1 error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] gpt-image-1 exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # STABILITY AI
    # =========================================================================
    def _generate_stability_ai(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using Stability AI API."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] Stability AI API key not configured")
            return None

        try:
            print(f"[AI-IMG] Trying Stability AI for: {label[:40]}...")
            response = requests.post(
                "https://api.stability.ai/v2beta/stable-image/generate/core",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Accept": "image/*",
                },
                files={"none": ""},
                data={
                    "prompt": prompt,
                    "output_format": "png",
                    "aspect_ratio": "16:9",
                },
                timeout=60,
            )

            if response.status_code == 200:
                with open(output_path, "wb") as f:
                    f.write(response.content)
                print(f"[AI-IMG] OK (stability-ai): {output_path}")
                return output_path
            else:
                try:
                    err = response.json().get("message", response.text[:200])
                except Exception:
                    err = response.text[:200]
                print(f"[AI-IMG] Stability AI error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] Stability AI exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # POST-PROCESSING: Background Removal
    # =========================================================================
    def _try_remove_background(self, image_path: str):
        """Remove background from generated image for cleaner whiteboard compositing."""
        try:
            from rembg import remove
            from PIL import Image

            img = Image.open(image_path)
            result = remove(img)
            white_bg = Image.new("RGBA", result.size, (255, 255, 255, 255))
            white_bg.paste(result, mask=result.split()[3] if result.mode == "RGBA" else None)
            white_bg.convert("RGB").save(image_path, "PNG")
            print(f"[AI-IMG] Background removed: {os.path.basename(image_path)}")
        except ImportError:
            pass
        except Exception as e:
            print(f"[AI-IMG] Background removal skipped: {str(e)[:100]}")
