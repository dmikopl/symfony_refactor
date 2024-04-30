<?php


namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CurrencyConverterCommandTest extends KernelTestCase
{
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the HttpClientInterface
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testExecute(): void
    {
        // Mock response data for bin and rate requests
        $binData = ['country' => ['alpha2' => 'DE']];
        $rateData = ['rates' => ['USD' => 1.2]];

        // Set up expectations for HTTP client requests
        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                ['GET', 'https://lookup.binlist.net/45717360'],
                ['GET', 'https://api.exchangeratesapi.io/latest']
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMockResponse($binData),
                $this->createMockResponse($rateData)
            );

        // Create the application
        $application = new Application();
        $application->add(new \App\Command\CurrencyConverterCommand($this->httpClient));

        // Set up command tester
        $command = $application->find('app:currency-converter');
        $commandTester = new CommandTester($command);

        // Execute the command with input.txt file in the main folder
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => 'C:\\xampp\\htdocs\\symfony_refactor\\input.txt'
        ]);

        // Assert the output
        $expectedOutput = "1\r\n0.42\r\n100\r\n1.09\r\n20\r\n"; // Updated expected output
        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    private function createMockResponse(array $data): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);
        $response->method('getStatusCode')->willReturn(200);
        return $response;
    }
}
