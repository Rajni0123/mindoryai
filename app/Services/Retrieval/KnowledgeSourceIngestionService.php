<?php

namespace App\Services\Retrieval;

use App\Models\DocumentChunk;
use App\Models\KnowledgeSource;
use App\Services\RAGService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

/**
 * Admin-upload knowledge source ingestion (PDF, DOCX, TXT, MD, URL, ZIP, question bank).
 */
class KnowledgeSourceIngestionService
{
    public function __construct(
        protected RAGService $ragService,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function ingestUploadedFile(
        UploadedFile $file,
        string $name,
        string $type,
        array $metadata = [],
        ?int $createdBy = null,
    ): KnowledgeSource {
        $providerKey = 'custom_' . Str::slug($name) . '_' . Str::random(6);
        $storedPath = $file->storeAs('knowledge-sources', $providerKey . '.' . $file->getClientOriginalExtension());

        $text = $this->extractFromPath(Storage::path($storedPath), $type);

        return $this->persistSource($name, $providerKey, $type, $storedPath, $text, $metadata, $createdBy);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function ingestUrl(string $name, string $url, array $metadata = [], ?int $createdBy = null): KnowledgeSource
    {
        $providerKey = 'custom_' . Str::slug($name) . '_' . Str::random(6);
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch URL content.');
        }

        $type = str_ends_with(strtolower($url), '.pdf') ? 'pdf' : 'url';
        $text = $type === 'pdf'
            ? $this->extractPdf(Storage::path($this->storeRaw($response->body(), $providerKey . '.pdf')))
            : strip_tags($response->body());

        return $this->persistSource($name, $providerKey, $type, $url, $text, $metadata, $createdBy);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function persistSource(
        string $name,
        string $providerKey,
        string $type,
        string $sourcePath,
        string $text,
        array $metadata,
        ?int $createdBy,
    ): KnowledgeSource {
        $source = KnowledgeSource::create([
            'name' => $name,
            'provider_key' => $providerKey,
            'type' => $type,
            'source_path' => $sourcePath,
            'metadata' => $this->normalizeMetadata($metadata),
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        $chunks = $this->chunkText($text);
        $count = 0;

        foreach ($chunks as $index => $chunkText) {
            $chunk = DocumentChunk::create([
                'knowledge_source_id' => $source->id,
                'provider_key' => $providerKey,
                'content' => $chunkText,
                'metadata' => array_merge($this->normalizeMetadata($metadata), [
                    'source' => $name,
                    'provider' => $providerKey,
                    'chunk_index' => $index,
                    'type' => $type,
                ]),
            ]);

            if ($this->ragService->generateChunkEmbedding($chunk)) {
                $count++;
            }
        }

        $source->update(['chunk_count' => $count]);

        return $source->fresh();
    }

    protected function extractFromPath(string $path, string $type): string
    {
        return match ($type) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            'zip' => $this->extractZip($path),
            default => trim(file_get_contents($path) ?: ''),
        };
    }

    protected function extractPdf(string $path): string
    {
        $config = new \Smalot\PdfParser\Config();
        $config->setRetainImageContent(false);
        $parser = new Parser([], $config);

        return trim(preg_replace('/\s+/', ' ', $parser->parseFile($path)->getText()) ?? '');
    }

    protected function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return trim($text);
    }

    protected function extractZip(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Invalid ZIP archive.');
        }

        $combined = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || str_ends_with($name, '/')) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if ($content === false) {
                continue;
            }

            $combined .= "\n" . $content;
        }

        $zip->close();

        return trim($combined);
    }

    /**
     * @return list<string>
     */
    protected function chunkText(string $text, int $size = 1200): array
    {
        $chunks = [];
        $length = strlen($text);

        for ($offset = 0; $offset < $length; $offset += $size) {
            $chunk = trim(substr($text, $offset, $size));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function normalizeMetadata(array $metadata): array
    {
        $defaults = [
            'subject' => null,
            'chapter' => null,
            'class' => null,
            'exam' => null,
            'difficulty' => null,
            'language' => 'english',
            'type' => null,
            'source' => null,
            'provider' => null,
            'year' => null,
            'board' => null,
            'topic' => null,
        ];

        return array_merge($defaults, $metadata);
    }

    protected function storeRaw(string $content, string $filename): string
    {
        $path = 'knowledge-sources/' . $filename;
        Storage::put($path, $content);

        return $path;
    }
}
