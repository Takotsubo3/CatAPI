<?php

namespace App\Command;

use App\Service\CatApiImporter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use JsonException;
use RuntimeException;

#[AsCommand(
    name: 'app:import-cats',
    description: 'Importe les races depuis The Cat API selon le mapping du projet.',
)]
final class ImportCatsCommand extends Command
{
    private const ENDPOINT = 'https://api.thecatapi.com/v1/breeds';

    protected function configure(): void
    {
        $this->addArgument(
            'resource',
            InputArgument::OPTIONAL,
            'Ressource à importer.',
            'breed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $resource = $input->getArgument('resource');

        if ($resource !== 'breed') {
            $io->error('La seule ressource supportée par le mapping est « breed ».');

            return Command::INVALID;
        }

        if ($this->apiKey === '') {
            $io->error('THE_CAT_API_KEY doit être défini dans .env.local ou dans l’environnement.');

            return Command::FAILURE;
        }

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT, [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);
            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                throw new RuntimeException(sprintf('The Cat API a répondu avec le statut HTTP %d.', $statusCode));
            }

            $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload) || !array_is_list($payload)) {
                throw new RuntimeException('La réponse de The Cat API doit être un tableau JSON de races.');
            }

            /** @var array<int, array<string, mixed>> $payload */
            $result = $this->importer->import($payload);
        } catch (TransportExceptionInterface $exception) {
            $io->error('Impossible de contacter The Cat API : '.$exception->getMessage());

            return Command::FAILURE;
        } catch (JsonException|RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('%d race(s) importée(s), %d ignorée(s).', $result->imported, $result->skipped));

        return Command::SUCCESS;
    }

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CatApiImporter $importer,
        #[Autowire('%env(string:THE_CAT_API_KEY)%')]
        private readonly string $apiKey,
    ) {
        parent::__construct();
    }
}
