<?php

namespace App\Console\Commands;

use App\Models\DnsAccount;
use App\Services\DNS\DnsManager;
use Illuminate\Console\Command;

class ImportDomains extends Command
{
    protected $signature = 'domains:import 
                            {file : Path to file with domains (one per line)}
                            {--account= : DNS account ID}
                            {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import domains from a text file';

    public function handle(DnsManager $dnsManager): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        // Get DNS account
        $accountId = $this->option('account');
        if ($accountId) {
            $account = DnsAccount::find($accountId);
        } else {
            $accounts = DnsAccount::where('is_active', true)->get();
            if ($accounts->isEmpty()) {
                $this->error("No active DNS accounts found");
                return self::FAILURE;
            }

            $accountId = $this->choice(
                'Select DNS account',
                $accounts->pluck('name', 'id')->toArray()
            );
            $account = DnsAccount::find($accountId);
        }

        if (!$account) {
            $this->error("DNS account not found");
            return self::FAILURE;
        }

        // Read domains
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $domains = array_filter(array_map('trim', $lines));

        $this->info("Found " . count($domains) . " domains in file");
        $this->info("DNS Account: {$account->name} ({$account->provider})");

        if ($this->option('dry-run')) {
            $this->info("DRY RUN - No changes will be made");
            $this->table(['Domain'], array_map(fn($d) => [$d], array_slice($domains, 0, 20)));
            if (count($domains) > 20) {
                $this->info("... and " . (count($domains) - 20) . " more");
            }
            return self::SUCCESS;
        }

        if (!$this->confirm("Proceed with import?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($domains));
        $bar->start();

        $results = $dnsManager->addDomainsBulk($account, implode("\n", $domains));

        $bar->finish();
        $this->newLine(2);

        $this->info("Import completed!");
        $this->table(['Status', 'Count'], [
            ['Added', $results['summary']['added'] ?? 0],
            ['Failed', $results['summary']['failed'] ?? 0],
            ['Invalid', $results['summary']['invalid'] ?? 0],
        ]);

        if (!empty($results['failed'])) {
            $this->warn("Failed domains:");
            foreach (array_slice($results['failed'], 0, 10) as $fail) {
                $this->line("  - {$fail['domain']}: {$fail['error']}");
            }
        }

        return self::SUCCESS;
    }
}
