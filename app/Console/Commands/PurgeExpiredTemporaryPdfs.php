<?php

namespace App\Console\Commands;

use App\Models\TemporaryPdfRetrieval;
use Illuminate\Console\Command;

class PurgeExpiredTemporaryPdfs extends Command
{
    protected $signature = 'retrieval:purge-temp-pdfs';

    protected $description = 'Delete expired temporary PDF retrieval records';

    public function handle(): int
    {
        $deleted = TemporaryPdfRetrieval::where('expires_at', '<=', now())->delete();
        $this->info("Purged {$deleted} expired temporary PDF records.");

        return self::SUCCESS;
    }
}
