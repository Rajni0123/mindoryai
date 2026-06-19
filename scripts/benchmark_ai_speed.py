#!/usr/bin/env python3
"""
Benchmark OpenAI chat speed — compare streaming vs non-streaming.
Usage:
  set OPENAI_API_KEY=sk-...
  python scripts/benchmark_ai_speed.py

GPT-like UX needs streaming TTFT (time to first token) under ~2 seconds.
"""
import json
import os
import sys
import time
import urllib.request

API_KEY = os.environ.get("OPENAI_API_KEY", "")
MODEL = os.environ.get("AI_CHAT_MODEL", "gpt-4o-mini")
QUESTION = "What is photosynthesis? Explain in 3 bullet points."


def post_json(url, payload, stream=False):
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Authorization": f"Bearer {API_KEY}",
            "Content-Type": "application/json",
        },
        method="POST",
    )
    return urllib.request.urlopen(req, timeout=60)


def test_non_streaming():
    print("\n=== NON-STREAMING (old mobile API behavior) ===")
    start = time.perf_counter()
    with post_json(
        "https://api.openai.com/v1/chat/completions",
        {
            "model": MODEL,
            "messages": [{"role": "user", "content": QUESTION}],
            "max_tokens": 300,
            "stream": False,
        },
    ) as resp:
        body = json.loads(resp.read().decode())
    total_ms = round((time.perf_counter() - start) * 1000)
    content = body["choices"][0]["message"]["content"]
    print(f"Model: {MODEL}")
    print(f"Total wait until full reply: {total_ms} ms")
    print(f"Reply preview: {content[:120]}...")
    return total_ms


def test_streaming():
    print("\n=== STREAMING (ChatGPT-like — first token fast) ===")
    start = time.perf_counter()
    ttft_ms = None
    chunks = []

    payload = {
        "model": MODEL,
        "messages": [{"role": "user", "content": QUESTION}],
        "max_tokens": 300,
        "stream": True,
    }
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        "https://api.openai.com/v1/chat/completions",
        data=data,
        headers={
            "Authorization": f"Bearer {API_KEY}",
            "Content-Type": "application/json",
        },
        method="POST",
    )

    with urllib.request.urlopen(req, timeout=60) as resp:
        for raw_line in resp:
            line = raw_line.decode("utf-8").strip()
            if not line.startswith("data: "):
                continue
            chunk = line[6:]
            if chunk == "[DONE]":
                break
            parsed = json.loads(chunk)
            delta = parsed.get("choices", [{}])[0].get("delta", {}).get("content", "")
            if delta:
                if ttft_ms is None:
                    ttft_ms = round((time.perf_counter() - start) * 1000)
                    print(f"First token (TTFT): {ttft_ms} ms  <-- user sees reply start here")
                chunks.append(delta)

    total_ms = round((time.perf_counter() - start) * 1000)
    print(f"Total stream time: {total_ms} ms")
    print(f"Reply preview: {''.join(chunks)[:120]}...")
    return ttft_ms, total_ms


def main():
    if not API_KEY:
        print("ERROR: Set OPENAI_API_KEY environment variable first.")
        print("  Windows: set OPENAI_API_KEY=sk-your-key")
        print("  Then run: python scripts/benchmark_ai_speed.py")
        sys.exit(1)

    print(f"BlinkStudy AI Speed Benchmark")
    print(f"Question: {QUESTION}")

    non_stream_ms = test_non_streaming()
    ttft_ms, stream_total_ms = test_streaming()

    print("\n=== SUMMARY ===")
    print(f"Non-streaming total:  {non_stream_ms} ms  (user waits with blank screen)")
    print(f"Streaming TTFT:         {ttft_ms} ms  (GPT-like — text starts appearing)")
    print(f"Streaming total:        {stream_total_ms} ms")
    if ttft_ms and non_stream_ms:
        saved = non_stream_ms - ttft_ms
        print(f"Perceived speed gain:   ~{saved} ms faster to first word")

    if ttft_ms and ttft_ms <= 2000:
        print("\n✅ TTFT under 2s — GPT-like speed achieved")
    elif ttft_ms:
        print(f"\n⚠️  TTFT {ttft_ms}ms — check network/API region or use gpt-4o-mini")


if __name__ == "__main__":
    main()
