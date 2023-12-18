<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use TYPO3\CMS\Core\Page\AssetCollector;

class AssetService
{
    /**
     * @param AssetCollector $assetCollector
     */
    public function __construct(private readonly AssetCollector $assetCollector)
    {
    }

    /**
     * add common assets
     *
     * @param array $settings
     * @return void
     */
    public function addCommonAssets(array $settings): void
    {
        $this->assetCollector->addJavaScript(
            'filemanager_explorer',
            'EXT:ameos_filemanager/Resources/Public/JavaScript/Explorer.js'
        );

        if (isset($settings['includeDefaultCss']) && $settings['includeDefaultCss']) {
            $this->assetCollector->addStyleSheet(
                'filemanager_style',
                'EXT:ameos_filemanager/Resources/Public/Css/style.css'
            );
        }
        if (isset($settings['includeFontawesome']) && $settings['includeFontawesome']) {
            $this->assetCollector->addStyleSheet(
                'filemanager_fontawesome',
                'EXT:ameos_filemanager/Resources/Public/Css/font-awesome/css/font-awesome.min.css'
            );
        }
    }

    /**
     * add dropzone
     *
     * @param string $uploadUri
     * @return void
     */
    public function addDropzone(string $uploadUri): void
    {
        $this->assetCollector->addStyleSheet(
            'filemanager_dropzone_css',
            'EXT:ameos_filemanager/Resources/Public/Css/dropzone/dropzone.css'
        );

        $this->assetCollector->addJavaScript(
            'filemanager_dropzone_js',
            'EXT:ameos_filemanager/Resources/Public/JavaScript/dropzone/dropzone.js'
        );

        $this->assetCollector->addInlineJavaScript(
            'filemanager_dropzone_inlinejs',
            '(function () {
                new Dropzone(document.querySelector(".uploadarea"), {
                    url: "' . $uploadUri . '",
                    init: function() {
                        this.on("success", function (file, response) {
                            var response = eval("(" + response + ")");

                            var additionalLinkNode = document.createElement("div");
                            additionalLinkNode.innerHTML = "<a target=\"_blank\" href=\"" + response.editUri + "\"> \
                                    {upload_label_edit} \
                                </a> \
                                <br> \
                                <a target=\"_blank\" href=\"" + response.infoUri + "\"> \
                                    {upload_label_detail} \
                                </a>";

                            file.previewElement.append(additionalLinkNode);
                        });
                    }
                });
            }).call(this)'
        );
    }
}
