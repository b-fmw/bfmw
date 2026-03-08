<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace bfmw\core;

use FilesystemIterator;

/**
 * Generates HTML tags for CSS and JavaScript assets found in a directory.
 */
class HtmlHeadGenerator
{
    const array FILE_TYPES = ['css', 'js'];
    private string $assetDirectory;
    private string $publicDirectory;

    /**
     * @param string $assetDirectory Filesystem directory containing assets.
     * @param string $publicDirectory Public URL prefix used in generated tags.
     */
    public function __construct(string $assetDirectory, string $publicDirectory)
    {
        $this->assetDirectory = rtrim($assetDirectory, DIRECTORY_SEPARATOR);
        $this->publicDirectory = rtrim($publicDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Builds all HTML tags for detected assets.
     *
     * @return string Concatenated HTML tags.
     */
    public function generate(): string
    {
        $tags = '';

        foreach ($this->listAssets() as $asset) {
            $tags .= $this->buildTag($asset);
        }

        return $tags;
    }

    /**
     * Lists supported assets from the configured directory.
     *
     * @return array<int,string>
     */
    private function listAssets(): array
    {
        $assets = [];

        foreach (new FilesystemIterator($this->assetDirectory) as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $extension = strtolower($fileInfo->getExtension());
            if (in_array($extension, self::FILE_TYPES)) {
                $assets[] = $fileInfo->getFilename();
            }
        }

        sort($assets);

        return $assets;
    }

    /**
     * Builds the HTML tag for a single asset file.
     *
     * @param string $fileName Asset file name.
     * @return string Generated tag, or an empty string when unsupported.
     */
    private function buildTag(string $fileName): string
    {
        $filePath = $this->assetDirectory . DIRECTORY_SEPARATOR . $fileName;
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($extension === 'css') {
            $media = strtolower($fileName) === 'print.css' ? ' media="print"' : '';

            return sprintf(
                "\t\t<link href=\"%s/%s?v=%s\" rel=\"stylesheet\" type=\"text/css\"%s>\n",
                $this->publicDirectory,
                $fileName,
                filemtime($filePath),
                $media
            );
        }

        if ($extension === 'js') {
            return sprintf(
                "\t\t<script src=\"%s/%s?v=%s\"></script>\n",
                $this->publicDirectory,
                $fileName,
                filemtime($filePath)
            );
        }

        return '';
    }
}
