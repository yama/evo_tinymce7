<?php
declare(strict_types=1);

namespace TinyMCE7\Editor;

use TinyMCE7\Config\ConfigRepository;
use TinyMCE7\Config\PreferenceResolver;
use TinyMCE7\Support\Language;

final class EditorInitializer
{
    private ElementSelector $elementSelector;
    private ConfigRepository $configRepository;
    private PreferenceResolver $preferences;
    private Language $language;
    private FileBrowserResolver $fileBrowserResolver;
    private ScriptFactory $scriptFactory;

    public function __construct(
        ?ElementSelector $elementSelector = null,
        ?ConfigRepository $configRepository = null,
        ?PreferenceResolver $preferences = null,
        ?Language $language = null,
        ?FileBrowserResolver $fileBrowserResolver = null,
        ?ScriptFactory $scriptFactory = null
    ) {
        $this->elementSelector = $elementSelector ?? new ElementSelector();
        $this->configRepository = $configRepository ?? new ConfigRepository();
        $this->preferences = $preferences ?? new PreferenceResolver($this->configRepository);
        $this->language = $language ?? new Language();
        $this->fileBrowserResolver = $fileBrowserResolver ?? new FileBrowserResolver();
        $this->scriptFactory = $scriptFactory ?? new ScriptFactory();
    }

    public function handle($event): void
    {
        $params = is_array($event->params ?? null) ? $event->params : [];
        $requestedEditor = (string)($params['editor'] ?? '');

        if ($requestedEditor !== 'TinyMCE7') {
            return;
        }

        $elements = $this->elementSelector->normalize($params['elements'] ?? []);
        if ($elements === []) {
            return;
        }

        $configPath = MODX_BASE_PATH . 'assets/plugins/tinymce7/config/' . (!empty($params['forfrontend']) ? 'frontend.json' : 'manager.json');
        $config = $this->configRepository->load($configPath);

        if (empty($config['selector'])) {
            $config['selector'] = $this->elementSelector->buildSelector($elements);
        }

        if (!empty($params['height']) && is_scalar($params['height'])) {
            $config['height'] = $params['height'];
        }

        if (!empty($params['width']) && is_scalar($params['width'])) {
            $config['width'] = $params['width'];
        }

        $uiLanguage = $this->language->detectUiLanguage();

        if (empty($config['language'])) {
            $config['language'] = $uiLanguage;
        }

        if (empty($config['language_url'])) {
            $config['language_url'] = $this->language->languageUrl((string)$config['language']);
        }
        $config['convert_urls'] = $config['convert_urls'] ?? false;
        $config['relative_urls'] = $config['relative_urls'] ?? false;

        $config = $this->preferences->applyToolbarPreset($config);
        $config = $this->preferences->applyMenubarPreference($config);
        $config = $this->preferences->applyEnterMode($config);

        [$config, $fileBrowser] = $this->fileBrowserResolver->resolve($config, $params);

        $configJson = $this->configRepository->encode($config);

        $scripts = [
            $this->scriptFactory->scriptTag($this->scriptFactory->tinymceScriptUrl()),
        ];

        if ($fileBrowser === 'mcpuk') {
            $scripts[] = $this->scriptFactory->inlineScript($this->fileBrowserResolver->mcpukBootstrapScript());
            $scripts[] = $this->scriptFactory->scriptTag(MODX_BASE_URL . 'assets/plugins/tinymce7/js/mcpuk-picker.js');
        }

        $output = [];
        $output[] = implode("\n", $scripts);
        $output[] = '<script>';
        $output[] = '(function() {';
        $output[] = '    if (typeof tinymce === "undefined") {';
        $output[] = '        console.error("TinyMCE 7 is not loaded. Check assets/plugins/tinymce7/tinymce/js/tinymce/tinymce.min.js");';
        $output[] = '        return;';
        $output[] = '    }';
        $output[] = '    const config = ' . $configJson . ';';
        $output[] = '    if (!config.selector) {';
        $output[] = '        console.warn("TinyMCE7: selector is empty. Please set selector in config file.");';
        $output[] = '        return;';
        $output[] = '    }';
        $output[] = '    switch (' . json_encode($fileBrowser) . ') {';
        $output[] = '        case "mcpuk":';
        $output[] = '            config.file_picker_callback = window.mceModxFilePicker || undefined;';
        $output[] = '            break;';
        $output[] = '        default:';
        $output[] = '            if (!config.file_picker_callback) {';
        $output[] = '                delete config.file_picker_callback;';
        $output[] = '            }';
        $output[] = '    }';
        $output[] = '    tinymce.init(config);';
        $output[] = '})();';
        $output[] = '</script>';

        $event->output(implode("\n", $output));
    }
}
