<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:currency-converter',
    description: 'Converts currency based on BIN and amount'
)]
class CurrencyConverterCommand extends Command
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Input file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        $rows = file($file);

        $responses = $this->httpClient->request('GET', 'https://lookup.binlist.net/45717360');
        $binData = $responses->toArray();
        $responses = $this->httpClient->request('GET', 'https://api.exchangeratesapi.io/latest');
        $rateData = $responses->toArray();

        foreach ($rows as $row) {
            $data = json_decode($row, true);
            $bin = $data['bin'];
            $amount = $data['amount'];
            $currency = $data['currency'];

            $isEu = $this->isEu($binData['country']['alpha2']);
            $rate = $rateData['rates'][$currency] ?? 0;

            $amntFixed = ($currency == 'EUR' || $rate == 0) ? $amount : $amount / $rate;
            $commission = $amntFixed * ($isEu == 'yes' ? 0.01 : 0.02);
            $commissionCeiled = ceil($commission * 100) / 100;

            $output->writeln($commissionCeiled);
        }

        return Command::SUCCESS;
    }

    private function isEu($countryCode)
    {
        $euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'];
        return in_array($countryCode, $euCountries) ? 'yes' : 'no';
    }
}
