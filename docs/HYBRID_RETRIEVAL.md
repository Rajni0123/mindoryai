# Hybrid Retrieval Engine

Perplexity-style orchestration layer extending the existing RAG without replacing it.

## Architecture

```
User Query → IntentClassifier → RetrievalRouter → Providers → HybridContextMerger → LLM
```

### Retrieval modes

| Mode | When |
|------|------|
| `rag_only` | NCERT, theory, formulas, indexed docs |
| `exa_only` | Current affairs, notifications, exam updates |
| `hybrid` | RAG + Exa (e.g. "explain topic + latest PYQs") |

## Opt-in (default OFF)

Hybrid mode is **disabled by default**. Existing RAG and chat flows are unchanged until enabled.

1. Run migration: `php artisan migrate`
2. Admin → **Hybrid Retrieval** → enable **Hybrid Engine**
3. Optionally set `EXA_API_KEY` or save key in admin UI

## Admin

- **Route:** `/admin/hybrid-retrieval`
- Toggle: Existing RAG, Exa, Hybrid, Redis cache, temp PDF, AI quiz fallback
- Configure provider/quiz priority JSON
- Upload knowledge sources (PDF, DOCX, TXT, MD, URL, ZIP)

## Providers

Registered via `ProviderRegistry` (strategy pattern):

- `ncert` — legacy `RAGService` (unchanged behavior)
- `pyq` — `exam_questions`
- `teacher_notes`, `formula` — chunk RAG by `provider_key`
- `exa` — Exa search API
- `custom_*` — admin-uploaded sources

## Extension points

- `RetrievalProviderInterface` — add new providers
- `IntentClassifierInterface` — swap rule-based classifier for LLM
- `config/retrieval.php` — defaults and TTLs

## Commands

```bash
php artisan retrieval:purge-temp-pdfs   # hourly via scheduler
```

## Tests

```bash
vendor/bin/phpunit tests/Unit/Retrieval
```
