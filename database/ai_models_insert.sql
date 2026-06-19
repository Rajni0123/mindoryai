-- Insert AI Models directly
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE ai_models;
SET FOREIGN_KEY_CHECKS=1;

INSERT INTO `ai_models` (`name`, `provider`, `model_identifier`, `api_url`, `description`, `icon`, `color`, `is_active`, `supports_vision`, `max_tokens`, `temperature`, `order`, `created_at`, `updated_at`) VALUES
('GPT-4o', 'openai', 'gpt-4o', 'https://api.openai.com/v1/chat/completions', 'Most advanced OpenAI model with vision support', '/images/ai-models/ChatGPT.png', '#10a37f', 1, 1, 4096, 0.70, 1, NOW(), NOW()),
('GPT-4o Mini', 'openai', 'gpt-4o-mini', 'https://api.openai.com/v1/chat/completions', 'Faster and more efficient GPT-4o variant', '/images/ai-models/ChatGPT.png', '#10a37f', 1, 1, 4096, 0.70, 2, NOW(), NOW()),
('GPT-4 Turbo', 'openai', 'gpt-4-turbo-preview', 'https://api.openai.com/v1/chat/completions', 'Fast and powerful GPT-4 model', '/images/ai-models/ChatGPT.png', '#10a37f', 1, 0, 4096, 0.70, 3, NOW(), NOW()),
('GPT-4', 'openai', 'gpt-4', 'https://api.openai.com/v1/chat/completions', 'Standard GPT-4 model for complex tasks', '/images/ai-models/ChatGPT.png', '#10a37f', 1, 0, 8192, 0.70, 4, NOW(), NOW()),
('GPT-3.5 Turbo', 'openai', 'gpt-3.5-turbo', 'https://api.openai.com/v1/chat/completions', 'Fast and efficient for everyday tasks', '/images/ai-models/ChatGPT.png', '#10a37f', 1, 0, 4096, 0.70, 5, NOW(), NOW()),
('Claude 3 Opus', 'anthropic', 'claude-3-opus-20240229', 'https://api.anthropic.com/v1/messages', 'Most powerful Claude model for complex tasks', '/images/ai-models/Claude.png', '#D97757', 1, 1, 4096, 0.70, 6, NOW(), NOW()),
('Claude 3 Sonnet', 'anthropic', 'claude-3-sonnet-20240229', 'https://api.anthropic.com/v1/messages', 'Balanced performance and speed', '/images/ai-models/Claude.png', '#D97757', 1, 1, 4096, 0.70, 7, NOW(), NOW()),
('Claude 3 Haiku', 'anthropic', 'claude-3-haiku-20240307', 'https://api.anthropic.com/v1/messages', 'Fastest Claude model for quick responses', '/images/ai-models/Claude.png', '#D97757', 1, 1, 4096, 0.70, 8, NOW(), NOW()),
('Claude 3.5 Sonnet', 'anthropic', 'claude-3-5-sonnet-20241022', 'https://api.anthropic.com/v1/messages', 'Latest improved Sonnet model', '/images/ai-models/Claude.png', '#D97757', 1, 1, 8192, 0.70, 9, NOW(), NOW()),
('Gemini Pro', 'google', 'gemini-pro', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', 'Google\'s powerful multimodal AI', '/images/ai-models/gemini.png', '#4285F4', 1, 0, 2048, 0.70, 10, NOW(), NOW()),
('Gemini Pro Vision', 'google', 'gemini-pro-vision', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent', 'Gemini with vision capabilities', '/images/ai-models/gemini.png', '#4285F4', 1, 1, 2048, 0.70, 11, NOW(), NOW()),
('DeepSeek Chat', 'deepseek', 'deepseek-chat', 'https://api.deepseek.com/v1/chat/completions', 'Advanced reasoning and coding model', '/images/ai-models/DeepSeek.png', '#6366f1', 1, 0, 4096, 0.70, 12, NOW(), NOW()),
('DeepSeek Coder', 'deepseek', 'deepseek-coder', 'https://api.deepseek.com/v1/chat/completions', 'Specialized for coding tasks', '/images/ai-models/DeepSeek.png', '#6366f1', 1, 0, 4096, 0.70, 13, NOW(), NOW());
