"""
BlinkStudy AI Microservice - FastAPI
Fast text-in → text-out AI service using Gemini.
Loaded once at startup, served async with uvicorn workers.
"""

import os
import time
import hashlib
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from dotenv import load_dotenv

load_dotenv()

# ── Config ────────────────────────────────────────────────────────
AI_API_KEY = os.getenv("AI_API_KEY", "")  # Gemini key
AI_MODEL = os.getenv("AI_MODEL", "gemini-2.0-flash")
SERVICE_API_KEY = os.getenv("SERVICE_API_KEY", "blinkstudy-ai-secret-2026")
CACHE_TTL = int(os.getenv("CACHE_TTL", "3600"))  # 1 hour default

# ── In-memory cache (per-worker, fast) ────────────────────────────
_cache: dict[str, dict] = {}


def cache_get(key: str) -> str | None:
    entry = _cache.get(key)
    if entry and time.time() - entry["ts"] < CACHE_TTL:
        return entry["value"]
    if entry:
        del _cache[key]
    return None


def cache_set(key: str, value: str):
    # Cap cache at 2000 entries to prevent memory leak
    if len(_cache) > 2000:
        oldest = sorted(_cache, key=lambda k: _cache[k]["ts"])[:500]
        for k in oldest:
            del _cache[k]
    _cache[key] = {"value": value, "ts": time.time()}


def make_key(text: str, system_prompt: str) -> str:
    raw = f"{text}||{system_prompt}"
    return hashlib.sha256(raw.encode()).hexdigest()


# ── AI Client (loaded once at startup) ───────────────────────────
ai_client = None


def load_ai_client():
    global ai_client
    if not AI_API_KEY:
        print("[WARN] AI_API_KEY not set - AI calls will fail")
        return
    from google import genai
    ai_client = genai.Client(api_key=AI_API_KEY)
    print(f"[AI-SERVER] Gemini client loaded. Model: {AI_MODEL}")


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load AI model once at startup."""
    load_ai_client()
    print(f"[AI-SERVER] Ready. Cache TTL: {CACHE_TTL}s")
    yield
    print("[AI-SERVER] Shutting down.")


# ── FastAPI App ──────────────────────────────────────────────────
app = FastAPI(
    title="BlinkStudy AI Service",
    version="1.0.0",
    lifespan=lifespan,
)


# ── Auth dependency ──────────────────────────────────────────────
async def verify_api_key(x_api_key: str = Header(alias="X-API-Key", default="")):
    if not SERVICE_API_KEY:
        return  # No key configured = open (dev mode)
    if x_api_key != SERVICE_API_KEY:
        raise HTTPException(status_code=401, detail="Invalid API key")


# ── Request/Response models ──────────────────────────────────────
class AIRequest(BaseModel):
    text: str
    system_prompt: str = "You are a helpful educational AI assistant. Be concise and accurate."
    max_tokens: int = 2048
    temperature: float = 0.7


class AIResponse(BaseModel):
    answer: str
    cached: bool = False
    model: str = ""
    latency_ms: int = 0


# ── Endpoints ────────────────────────────────────────────────────
@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "blinkstudy-ai-server",
        "model": AI_MODEL,
        "client_loaded": ai_client is not None,
        "cache_size": len(_cache),
    }


@app.post("/ai", response_model=AIResponse, dependencies=[Depends(verify_api_key)])
async def process_ai(req: AIRequest):
    """Main AI endpoint. Text in → text out. Cached."""
    start = time.time()

    if not req.text or not req.text.strip():
        raise HTTPException(status_code=400, detail="text is required")

    # Check cache
    key = make_key(req.text, req.system_prompt)
    cached = cache_get(key)
    if cached:
        return AIResponse(
            answer=cached,
            cached=True,
            model=AI_MODEL,
            latency_ms=int((time.time() - start) * 1000),
        )

    # Call Gemini
    if not ai_client:
        raise HTTPException(status_code=503, detail="AI client not loaded. Check AI_API_KEY.")

    try:
        from google.genai import types

        response = ai_client.models.generate_content(
            model=AI_MODEL,
            contents=req.text,
            config=types.GenerateContentConfig(
                system_instruction=req.system_prompt,
                temperature=req.temperature,
                max_output_tokens=req.max_tokens,
            ),
        )

        answer = response.text.strip()
        if not answer:
            raise HTTPException(status_code=502, detail="Empty response from AI model")

        # Cache it
        cache_set(key, answer)

        return AIResponse(
            answer=answer,
            cached=False,
            model=AI_MODEL,
            latency_ms=int((time.time() - start) * 1000),
        )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"AI error: {str(e)}")


@app.post("/ai/batch", dependencies=[Depends(verify_api_key)])
async def process_batch(requests: list[AIRequest]):
    """Process multiple AI requests. Returns list of responses."""
    if len(requests) > 10:
        raise HTTPException(status_code=400, detail="Max 10 requests per batch")

    results = []
    for req in requests:
        try:
            result = await process_ai(req)
            results.append(result.model_dump())
        except HTTPException as e:
            results.append({"answer": "", "error": e.detail, "cached": False})

    return {"results": results}


@app.delete("/cache", dependencies=[Depends(verify_api_key)])
async def clear_cache():
    """Clear in-memory cache."""
    count = len(_cache)
    _cache.clear()
    return {"cleared": count}
