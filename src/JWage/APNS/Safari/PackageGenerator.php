<?php

namespace JWage\APNS\Safari;

use ErrorException;
use JWage\APNS\Certificate;
use ZipArchive;

class PackageGenerator
{
    /**
     * @var \JWage\APNS\Certificate
     */
    protected $certificate;
    /**
     * @var string
     */
    protected $basePushPackagePath;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $websiteName;
    /**
     * @var string
     */
    protected $websitePushId;
    /**
     * @var string
     */
    protected $webServiceHost;
    /**
     * @var string
     */
    protected $pushSubDomains;
    /**
     * @var string
     */
    protected $clientId;

    /**
     * Construct.
     *
     * @param \JWage\APNS\Certificate $certificate
     * @param string $basePushPackagePath
     * @param string $host
     * @param mixed $pushSubDomains ;
     * @param string $websiteName
     * @param string $websitePushId
     * @param string $webServiceHost
     */
    public function __construct(
        Certificate $certificate,
        $basePushPackagePath,
        $host,
        $pushSubDomains,
        $websiteName = '',
        $websitePushId = '',
        $webServiceHost = null
    )
    {
        $this->certificate = $certificate;
        $this->basePushPackagePath = $basePushPackagePath;
        $this->host = $host;
        $this->pushSubDomains = $this->formatPushSubDomains($pushSubDomains, $host);
        $this->websiteName = $websiteName;
        $this->websitePushId = $websitePushId;
        $this->webServiceHost = $webServiceHost ? $webServiceHost : $host;
    }

    /**
     * @param mixed $value
     * @param string $default
     * @param bool $toPrintOut Doesn't return the result, just echo it.
     * @return mixed
     */
    private function formatPushSubDomains($value, $default, $toPrintOut = false)
    {
        if ($value) {
            $temporaryPushSubDomains = is_array($value) ? $value : [$value];
        } else {
            $temporaryPushSubDomains = [$default];
        }

        $isMultiple = count($value) == 1;

        $temporaryPushSubDomains = array_map(function ($value) use ($isMultiple) {
            $newValue = (substr_count($value, 'http') > 0) ? "\"$value\"" : "\"https://$value\"";

            return $isMultiple ? $newValue : "$newValue, ";
        }, $temporaryPushSubDomains);

        $temporaryPushSubDomains = join("", $temporaryPushSubDomains);

        if ($toPrintOut) {
            echo $temporaryPushSubDomains;
        }

        return $temporaryPushSubDomains;
    }

    /**
     * Create a safari website push notification package for the given User.
     *
     * @param string $userId User id to create package for.
     * * @param string $clientId Client id to create package for.
     * @return \JWage\APNS\Safari\Package $package Package instance.
     */
    public function createPushPackageForUser($userId, $clientId)
    {
        $packageDir = sprintf('/%s/pushPackage%s.%s', sys_get_temp_dir(), time(), $userId);
        $package = $this->createPackage($packageDir, $userId, $clientId);

        $this->generatePackage($package);

        return $package;
    }

    /**
     * @param string $packageDir
     * @param string $userId
     * @param string $clientId
     */
    protected function createPackage($packageDir, $userId, $clientId)
    {
        return new Package($packageDir, $userId, $clientId);
    }

    private function generatePackage(Package $package)
    {
        $packageDir = $package->getPackageDir();
        $zipPath = $package->getZipPath();

        if (!is_dir($packageDir)) {
            mkdir($packageDir);
        }

        $this->copyPackageFiles($package);
        $this->createPackageManifest($package);
        $this->createPackageSignature($package);

        $zip = $this->createZipArchive();

        if (!$zip->open($zipPath, ZipArchive::CREATE)) {
            throw new ErrorException(sprintf('Could not open package "%s"', $zipPath));
        }

        $packageFiles = Package::$packageFiles;
        $packageFiles[] = 'manifest.json';
        $packageFiles[] = 'signature';

        foreach ($packageFiles as $packageFile) {
            $filePath = sprintf('%s/%s', $packageDir, $packageFile);

            if (!file_exists($filePath)) {
                throw new ErrorException(sprintf('File does not exist "%s"', $filePath));
            }

            $zip->addFile($filePath, $packageFile);
        }

        if (false === $zip->close()) {
            throw new ErrorException(sprintf('Could not save package "%s"', $zipPath));
        }
    }

    private function copyPackageFiles(Package $package)
    {
        $packageDir = $package->getPackageDir();

        mkdir($packageDir . '/icon.iconset');

        foreach (Package::$packageFiles as $rawFile) {
            $filePath = sprintf('%s/%s', $packageDir, $rawFile);

            copy(sprintf('%s/%s', $this->basePushPackagePath, $rawFile), $filePath);

            if ($rawFile === 'website.json') {
                $websiteJson = file_get_contents($filePath);
                $websiteJson = str_replace('{{ userId }}', $package->getUserId(), $websiteJson);
                $websiteJson = str_replace('{{ clientId }}', $package->getClientId(), $websiteJson);
                $websiteJson = str_replace('{{ host }}', $this->host, $websiteJson);
                $websiteJson = str_replace('{{ pushSubDomain }}', $this->pushSubDomains, $websiteJson);
                $websiteJson = str_replace('{{ websiteName }}', $this->websiteName, $websiteJson);
                $websiteJson = str_replace('{{ websitePushId }}', $this->websitePushId, $websiteJson);
                $websiteJson = str_replace('{{ webServiceHost }}', $this->webServiceHost, $websiteJson);

                file_put_contents($filePath, $websiteJson);
            }
        }
    }

    private function createPackageManifest(Package $package)
    {
        return $this->createPackageManifester()->createManifest($package);
    }

    protected function createPackageManifester()
    {
        return new PackageManifester();
    }

    private function createPackageSignature(Package $package)
    {
        return $this->createPackageSigner()->createPackageSignature(
            $this->certificate, $package
        );
    }

    protected function createPackageSigner()
    {
        return new PackageSigner();
    }

    protected function createZipArchive()
    {
        return new ZipArchive();
    }
}
