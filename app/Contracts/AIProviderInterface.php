<?php

namespace App\Contracts;

interface AIProviderInterface
{
    /**
     * Send a chat completion request
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param array $options Additional options like temperature, max_tokens, etc.
     * @return string The AI response content
     * @throws \Exception
     */
    public function chat(array $messages, array $options = []): string;

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the model identifier
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Check if the provider is available/configured
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
