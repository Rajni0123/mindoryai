# BlinkStudy AI Architecture

Canonical request flow for chat, doubts, scan/solve, and hybrid retrieval.

## Stack diagram

```
Flutter App / Web App (chat.blinkstudy.in)
        │
        ▼
Laravel API (api.blinkstudy.in / routes/api.php + chat routes)
        │
        ▼
AI Orchestrator
  • UnifiedAIService        → model routing, prompts, OpenAI/GPT call
  • RetrievalOrchestrator   → intent → RAG / Exa / hybrid merge
        │
 ┌──────┼─────────┐
 ▼      ▼         ▼
RAG    Exa      OpenAI
 │       │         │
 │   ExaSearch     GPT models
 │   Service       (gpt-4o-mini / gpt-4o)
 │       │         │
NCERT   Web       Final
PYQ     highlights  completion
chunks  + URLs
 │       │         │
 └───────┼─────────┘
         ▼
    Final Response
```

## Layer responsibilities

| Layer | Code | Role |
|-------|------|------|
| Clients | Flutter app, `chat.blade.php` | UI only — no secrets, no direct Exa/OpenAI |
| Laravel API | `MobileChatController`, `Api\ChatController`, `ImageAnalysisController` | Auth, limits, validation, JSON/stream responses |
| AI Orchestrator | `UnifiedAIService` + `RetrievalOrchestrator` | Single entry for AI + retrieval |
| RAG | `RAGService`, `NcertRagProvider`, `PyqRagProvider`, chunk providers | NCERT, PYQ, teacher notes, uploaded sources |
| Exa | `ExaSearchService` | Web search when intent or Globe Search mode |
| OpenAI | `UnifiedAIService::sendToOpenAI()` | Final answer generation with injected context |

## Request path (chat)

1. **Client** → `POST /api/chats/{id}/messages` (Flutter) or `POST /api/chat/send` (web)
2. **Controller** → `MobileChatController` / `Api\ChatController`
3. **Orchestrator** → `UnifiedAIService::chat()`
4. **Retrieval** (if hybrid enabled) → `maybeAttachRetrievalContext()` → `RetrievalOrchestrator::retrieve()`
   - `rag_only` → NCERT / PYQ / chunks
   - `exa_only` → Exa API
   - `hybrid` → merge RAG + Exa
   - Globe **Search** mode → force `exa_only`
5. **LLM** → OpenAI with system prompt + `RETRIEVED KNOWLEDGE` block
6. **Response** → JSON or SSE stream to client

## Admin toggles (no code deploy needed)

| Toggle | Config key | Effect |
|--------|------------|--------|
| Hybrid engine | `retrieval.hybrid_enabled` | Master switch for retrieval layer |
| Exa | `retrieval.exa_enabled` + `EXA_API_KEY` | Web search provider |
| Hybrid mode | `retrieval.hybrid_mode` | RAG + Exa together |
| OpenAI | `ai.openai_enabled` + key | GPT responses |
| Gemini | `ai.gemini_enabled` | Optional fallback (off in production) |

## Opt-in defaults

- Hybrid retrieval: **OFF** until admin enables
- Exa: **OFF** until key + toggle
- Existing RAG: **ON** when hybrid enabled
- Chat without hybrid: legacy RAG-only or pure GPT (unchanged)

## Related docs

- [HYBRID_RETRIEVAL.md](./HYBRID_RETRIEVAL.md) — providers, Exa env, admin UI
- `config/retrieval.php` — retrieval defaults
- `config/ai.php` — GPT model stack (`AI_QUIZ_MODEL`, `AI_CHAT_MODEL`, etc.)

## Legacy note

`ConversationController::sendMessage` still calls OpenAI directly for one web stream path. New features should use `UnifiedAIService` only so RAG + Exa + GPT stay in one pipeline.
