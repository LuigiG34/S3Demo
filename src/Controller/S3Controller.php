<?php
namespace App\Controller;

use App\Entity\User;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class S3Controller extends AbstractController
{
    private $s3Client;
    private $bucket;
    private $logger;

    public function __construct(string $awsAccessKeyId, string $awsSecretAccessKey, string $awsRegion, string $awsBucket, LoggerInterface $logger)
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $awsRegion,
            'credentials' => [
                'key' => $awsAccessKeyId,
                'secret' => $awsSecretAccessKey,
            ],
        ]);
        $this->bucket = $awsBucket;
        $this->logger = $logger;
    }

    #[Route('/export-users', name: 'export_users')]
    public function exportUsers(EntityManagerInterface $em): Response
    {
        try {
            $users = $em->getRepository(User::class)->findAll();
            $csv = "id,name,email\n";
            foreach ($users as $user) {
                $csv .= sprintf("%d,%s,%s\n", $user->getId(), $user->getName(), $user->getEmail());
            }

            $filename = 'users-' . date('Y-m-d-His') . '.csv';
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $filename,
                'Body' => $csv,
                'ContentType' => 'text/csv',
            ]);
            $this->logger->info("Uploaded CSV to S3: $filename");

            return $this->render('s3/export.html.twig', ['filename' => $filename]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to export CSV: " . $e->getMessage());
            throw $this->createNotFoundException('Failed to export CSV');
        }
    }

    #[Route('/download-csv/{filename}', name: 'download_csv')]
    public function downloadCsv(string $filename): RedirectResponse
    {
        try {
            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $filename,
            ]);
            $request = $this->s3Client->createPresignedRequest($command, '+10 minutes');
            $presignedUrl = (string) $request->getUri();
            $this->logger->info("Generated presigned URL for: $filename");
            return new RedirectResponse($presignedUrl);
        } catch (\Exception $e) {
            $this->logger->error("Failed to generate presigned URL: " . $e->getMessage());
            throw $this->createNotFoundException('Failed to generate presigned URL');
        }
    }
}