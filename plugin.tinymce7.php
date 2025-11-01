<?php
if (!defined('MODX_BASE_PATH')) {
    die('No direct access allowed.');
}

if (!function_exists('evo')) {
    /** @var DocumentParser $modx */
    global $modx;
    if (!isset($modx) || !is_object($modx)) {
        die('Evolution CMS context not available.');
    }
    function evo()
    {
        /** @var DocumentParser $modx */
        global $modx;
        return $modx;
    }
}

if (!function_exists('tinymce7HandleInit')) {
    function tinymce7HandleInit(): void
    {
        $event = evo()->event;
        $params = is_array($event->params) ? $event->params : [];
        $requestedEditor = (string)($params['editor'] ?? '');

        if ($requestedEditor !== 'TinyMCE7') {
            return;
        }

        $elements = tinymce7NormalizeElements($params['elements'] ?? []);
        if ($elements === []) {
            return;
        }

        $configPath = MODX_BASE_PATH . 'assets/plugins/tinymce7/config/' . (!empty($params['forfrontend']) ? 'frontend.json' : 'manager.json');
        $config = tinymce7LoadConfig($configPath);

        if (empty($config['selector'])) {
            $config['selector'] = tinymce7BuildSelector($elements);
        }

        if (!empty($params['height']) && is_scalar($params['height'])) {
            $config['height'] = $params['height'];
        }

        if (!empty($params['width']) && is_scalar($params['width'])) {
            $config['width'] = $params['width'];
        }

        $uiLanguage = tinymce7DetectUiLanguage();

        if (empty($config['language'])) {
            $config['language'] = $uiLanguage;
        }

        if (empty($config['language_url'])) {
            $config['language_url'] = tinymce7LanguageUrl($config['language']);
        }
        $config['convert_urls'] = $config['convert_urls'] ?? false;
        $config['relative_urls'] = $config['relative_urls'] ?? false;

        $config = tinymce7ApplyToolbarPreset($config);
        $config = tinymce7ApplyMenubarPreference($config);
        $config = tinymce7ApplyEnterMode($config);

        [$config, $fileBrowser] = tinymce7ResolveFileBrowser($config, $params);

        $configJson = tinymce7EncodeConfig($config);

        $scripts = [
            tinymce7ScriptTag(tinymce7ScriptUrl()),
        ];

        if ($fileBrowser === 'elfinder') {
            $scripts[] = tinymce7ScriptTag(MODX_BASE_URL . 'assets/plugins/tinymce7/js/elfinder-picker.js');
        } elseif ($fileBrowser === 'mcpuk') {
            $scripts[] = tinymce7InlineScript(tinymce7McpukBootstrapScript());
            $scripts[] = tinymce7ScriptTag(MODX_BASE_URL . 'assets/plugins/tinymce7/js/mcpuk-picker.js');
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
        $output[] = '        case "elfinder":';
        $output[] = '            config.file_picker_callback = window.mceElfinderPicker || undefined;';
        $output[] = '            break;';
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

$event = evo()->event;
$eventName = $event->name ?? '';

switch ($eventName) {
    case 'OnRichTextEditorRegister':
        $event->output('TinyMCE7');
        break;

    case 'OnRichTextEditorInit':
        tinymce7HandleInit();
        break;

    case 'OnInterfaceSettingsRender':
        $event->output(tinymce7RenderSystemSettingsTab());
        break;
}

if (!function_exists('tinymce7LoadConfig')) {
    function tinymce7LoadConfig(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('tinymce7EncodeConfig')) {
    function tinymce7EncodeConfig(array $config): string
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '{}';
        }

        return $json;
    }
}

if (!function_exists('tinymce7NormalizeElements')) {
    function tinymce7NormalizeElements($elements): array
    {
        if (is_string($elements)) {
            $elements = explode(',', $elements);
        }

        if (!is_array($elements)) {
            return [];
        }

        $elements = array_filter(array_map('trim', $elements));

        return array_values(array_unique($elements));
    }
}

if (!function_exists('tinymce7BuildSelector')) {
    function tinymce7BuildSelector(array $elements): string
    {
        if ($elements === []) {
            return '';
        }

        $selectors = array_map(static function ($element) {
            return '#' . ltrim((string)$element, '#');
        }, $elements);

        return implode(',', $selectors);
    }
}

if (!function_exists('tinymce7ScriptUrl')) {
    function tinymce7ScriptUrl(): string
    {
        $localPath = MODX_BASE_PATH . 'assets/plugins/tinymce7/tinymce/js/tinymce/tinymce.min.js';
        if (is_file($localPath)) {
            return MODX_BASE_URL . 'assets/plugins/tinymce7/tinymce/js/tinymce/tinymce.min.js';
        }

        return 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
    }
}

if (!function_exists('tinymce7ResolveFileBrowser')) {
    function tinymce7ResolveFileBrowser(array $config, array $params): array
    {
        $browser = null;

        if (!empty($params['tinymce7_file_browser']) && is_string($params['tinymce7_file_browser'])) {
            $browser = strtolower((string)$params['tinymce7_file_browser']);
        } elseif (!empty($params['file_browser']) && is_string($params['file_browser'])) {
            $browser = strtolower((string)$params['file_browser']);
        } elseif (isset($config['tinymce7_file_browser']) && is_string($config['tinymce7_file_browser'])) {
            $browser = strtolower((string)$config['tinymce7_file_browser']);
            unset($config['tinymce7_file_browser']);
        }

        switch ($browser) {
            case 'elfinder':
                return [$config, 'elfinder'];
            case 'mcpuk':
            case null:
                return [$config, 'mcpuk'];
            case 'none':
            default:
                return [$config, 'none'];
        }
    }
}

if (!function_exists('tinymce7ApplyEnterMode')) {
    function tinymce7ApplyEnterMode(array $config): array
    {
        if (array_key_exists('newline_behavior', $config)) {
            return $config;
        }

        $hasLegacyNewlineFlags = false;

        if (isset($config['force_br_newlines']) || isset($config['force_p_newlines'])) {
            unset($config['force_br_newlines'], $config['force_p_newlines']);
            $hasLegacyNewlineFlags = true;
        }

        if (isset($config['forced_root_block']) && !in_array($config['forced_root_block'], ['', 'p'], true)) {
            return $config;
        }

        $mode = tinymce7DetectEnterMode();

        if ($mode === null && !$hasLegacyNewlineFlags) {
            return $config;
        }

        unset($config['forced_root_block']);

        if ($mode === 'br' || ($mode === null && $hasLegacyNewlineFlags)) {
            $config['newline_behavior'] = 'linebreak';
        } elseif ($mode === 'p' || $mode === null) {
            $config['newline_behavior'] = 'default';
        }

        return $config;
    }
}

if (!function_exists('tinymce7DetectToolbarPreset')) {
    function tinymce7DetectToolbarPreset(): string
    {
        $keys = ['tinymce7_toolbar_preset', 'tinymce_toolbar_preset'];

        $aliases = [
            'simple' => 'simple',
            'basic' => 'basic',
            'legacy' => 'legacy',
            'classic' => 'legacy',
            'full' => 'legacy',
            'advanced' => 'legacy',
        ];

        foreach ($keys as $key) {
            if (!isset(evo()->config[$key])) {
                continue;
            }

            $value = strtolower(trim((string)evo()->config[$key]));

            if ($value === '') {
                continue;
            }

            if (isset($aliases[$value])) {
                return $aliases[$value];
            }
        }

        return 'legacy';
    }
}

if (!function_exists('tinymce7DetectMenubarPreference')) {
    function tinymce7DetectMenubarPreference(): ?bool
    {
        $keys = ['tinymce7_menubar', 'tinymce_menubar'];

        foreach ($keys as $key) {
            if (!array_key_exists($key, evo()->config)) {
                continue;
            }

            $raw = evo()->config[$key];

            if (is_bool($raw)) {
                return $raw;
            }

            if (is_int($raw)) {
                return $raw !== 0;
            }

            if (is_string($raw)) {
                $value = strtolower(trim($raw));

                if ($value === '') {
                    continue;
                }

                if (in_array($value, ['1', 'true', 'yes', 'on', 'show'], true)) {
                    return true;
                }

                if (in_array($value, ['0', 'false', 'no', 'off', 'hide'], true)) {
                    return false;
                }
            }

            if (is_float($raw)) {
                return (int)$raw !== 0;
            }
        }

        return null;
    }
}

if (!function_exists('tinymce7DetectEnterMode')) {
    function tinymce7DetectEnterMode(): ?string
    {
        $keys = ['tinymce7_entermode', 'tinymce4_entermode', 'tinymce_entermode'];

        foreach ($keys as $key) {
            if (!isset(evo()->config[$key])) {
                continue;
            }

            $value = strtolower(trim((string)evo()->config[$key]));

            if ($value === 'br' || $value === 'p') {
                return $value;
            }
        }

        return null;
    }
}

if (!function_exists('tinymce7LoadToolbarPresets')) {
    function tinymce7LoadToolbarPresets(): array
    {
        static $presets;

        if ($presets !== null) {
            return $presets;
        }

        $path = MODX_BASE_PATH . 'assets/plugins/tinymce7/config/toolbar-presets.json';
        if (!is_file($path)) {
            $presets = [];

            return $presets;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            $presets = [];

            return $presets;
        }

        $data = json_decode($json, true);
        $presets = is_array($data) ? $data : [];

        return $presets;
    }
}

if (!function_exists('tinymce7ApplyToolbarPreset')) {
    function tinymce7ApplyToolbarPreset(array $config): array
    {
        $preset = tinymce7DetectToolbarPreset();
        $presets = tinymce7LoadToolbarPresets();

        if (!isset($presets[$preset]) || !is_array($presets[$preset])) {
            return $config;
        }

        foreach ($presets[$preset] as $key => $value) {
            $config[$key] = $value;
        }

        return $config;
    }
}

if (!function_exists('tinymce7ApplyMenubarPreference')) {
    function tinymce7ApplyMenubarPreference(array $config): array
    {
        if (array_key_exists('menubar', $config)) {
            return $config;
        }

        $preference = tinymce7DetectMenubarPreference();

        if ($preference === null) {
            return $config;
        }

        $config['menubar'] = $preference;

        return $config;
    }
}

if (!function_exists('tinymce7LoadLexiconFor')) {
    function tinymce7LoadLexiconFor(string $language): array
    {
        $language = trim($language);
        if ($language === '') {
            return [];
        }

        $paths = [
            MODX_BASE_PATH . "manager/includes/lang/{$language}/tinymce7.inc.php",
            MODX_BASE_PATH . "manager/includes/lang/{$language}/tinymce7.php",
            MODX_BASE_PATH . "assets/plugins/tinymce7/langs/mgr/{$language}.inc.php",
            MODX_BASE_PATH . "assets/plugins/tinymce7/langs/mgr/{$language}.php",
        ];

        $lexicon = [];

        foreach ($paths as $file) {
            if (!is_file($file)) {
                continue;
            }

            $_lang = [];
            include $file;
            if (!empty($_lang) && is_array($_lang)) {
                $lexicon = array_merge($lexicon, $_lang);
            }
            unset($_lang);
        }

        return $lexicon;
    }
}

if (!function_exists('tinymce7Lexicon')) {
    function tinymce7Lexicon(): array
    {
        static $cachedLexicon;

        if (is_array($cachedLexicon)) {
            return $cachedLexicon;
        }

        $language = 'english';
        $modx = evo();
        if (is_object($modx) && isset($modx->config['manager_language'])) {
            $candidate = trim((string)$modx->config['manager_language']);
            if ($candidate !== '') {
                $language = $candidate;
            }
        }

        $languages = ['english'];
        if (strcasecmp($language, 'english') !== 0) {
            if (false !== $pos = strpos($language, '-')) {
                $base = substr($language, 0, $pos);
                if ($base !== '' && strcasecmp($base, 'english') !== 0) {
                    $languages[] = $base;
                }
            }

            $languages[] = $language;
        }

        $lexicon = [];

        foreach ($languages as $langKey) {
            $lexicon = array_merge($lexicon, tinymce7LoadLexiconFor($langKey));
        }

        $cachedLexicon = $lexicon;

        return $cachedLexicon;
    }
}

if (!function_exists('tinymce7Lang')) {
    function tinymce7Lang(string $key, string $default): string
    {
        $lexicon = tinymce7Lexicon();

        if (isset($lexicon[$key]) && is_string($lexicon[$key]) && $lexicon[$key] !== '') {
            return $lexicon[$key];
        }

        return $default;
    }
}

if (!function_exists('tinymce7RenderSystemSettingsTab')) {
    function tinymce7RenderSystemSettingsTab(): string
    {
        $toolbarPreset = tinymce7DetectToolbarPreset();
        $current = tinymce7DetectEnterMode();
        if ($current !== 'p' && $current !== 'br') {
            $current = '';
        }
        $menubarPreference = tinymce7DetectMenubarPreference();
        $menubarValue = '';
        if ($menubarPreference === true) {
            $menubarValue = '1';
        } elseif ($menubarPreference === false) {
            $menubarValue = '0';
        }
        $fieldId = 'tinymce7_entermode';
        $toolbarFieldId = 'tinymce7_toolbar_preset';
        $menubarFieldId = 'tinymce7_menubar';

        $toolbarOptions = [
            ['value' => 'simple', 'label' => tinymce7Lang('tinymce7_toolbar_simple', 'Simple')],
            ['value' => 'basic', 'label' => tinymce7Lang('tinymce7_toolbar_basic', 'Basic')],
            ['value' => 'legacy', 'label' => tinymce7Lang('tinymce7_toolbar_legacy', 'Legacy (Default)')],
        ];
        $options = [
            ['value' => '', 'label' => tinymce7Lang('tinymce7_entermode_default', 'TinyMCE default (paragraph)')],
            ['value' => 'p', 'label' => tinymce7Lang('tinymce7_entermode_p', 'Insert paragraph <p>')],
            ['value' => 'br', 'label' => tinymce7Lang('tinymce7_entermode_br', 'Insert line break <br>')],
        ];
        $menubarOptions = [
            ['value' => '', 'label' => tinymce7Lang('tinymce7_menubar_default', 'TinyMCE default (show)')],
            ['value' => '1', 'label' => tinymce7Lang('tinymce7_menubar_show', 'Show')],
            ['value' => '0', 'label' => tinymce7Lang('tinymce7_menubar_hide', 'Hide')],
        ];

        $html = [];
        $cssUrl = MODX_BASE_URL . 'assets/plugins/tinymce7/tinymce7.settings.css';
        $html[] = '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '">';
        $html[] = '<table id="editorRow_TinyMCE7" class="settings editorRow">';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th colspan="2" class="tinymce7-settings__header"><h4 class="tinymce7-settings__title">' . htmlspecialchars(tinymce7Lang('tinymce7_settings_header', 'TinyMCE 7'), ENT_QUOTES, 'UTF-8') . '</h4></th>';
        $html[] = '  </tr>';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th><label for="' . $toolbarFieldId . '">' . htmlspecialchars(tinymce7Lang('tinymce7_toolbar_label', 'Toolbar layout'), ENT_QUOTES, 'UTF-8') . '</label></th>';
        $html[] = '    <td>';
        $html[] = '      <select name="' . $toolbarFieldId . '" id="' . $toolbarFieldId . '" class="inputBox">';

        foreach ($toolbarOptions as $option) {
            $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
            $selected = ($toolbarPreset === $option['value']) ? ' selected="selected"' : '';
            $html[] = '            <option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }

        $html[] = '      </select>';
        $html[] = '      <div>' . htmlspecialchars(tinymce7Lang('tinymce7_toolbar_description', 'Choose the TinyMCE 7 toolbar configuration.'), ENT_QUOTES, 'UTF-8') . '</div>';
        $html[] = '    </td>';
        $html[] = '  </tr>';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th><label for="' . $menubarFieldId . '">' . htmlspecialchars(tinymce7Lang('tinymce7_menubar_label', 'Menubar visibility'), ENT_QUOTES, 'UTF-8') . '</label></th>';
        $html[] = '    <td>';
        $html[] = '      <select name="' . $menubarFieldId . '" id="' . $menubarFieldId . '" class="inputBox">';

        foreach ($menubarOptions as $option) {
            $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
            $selected = ($menubarValue === $option['value']) ? ' selected="selected"' : '';
            $html[] = '            <option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }

        $html[] = '      </select>';
        $html[] = '      <div>' . htmlspecialchars(tinymce7Lang('tinymce7_menubar_description', 'Choose whether TinyMCE 7 displays the menubar.'), ENT_QUOTES, 'UTF-8') . '</div>';
        $html[] = '    </td>';
        $html[] = '  </tr>';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th><label for="' . $fieldId . '">' . htmlspecialchars(tinymce7Lang('tinymce7_entermode_label', 'Enter key behavior'), ENT_QUOTES, 'UTF-8') . '</label></th>';
        $html[] = '    <td>';
        $html[] = '      <select name="' . $fieldId . '" id="' . $fieldId . '" class="inputBox">';

        foreach ($options as $option) {
            $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
            $selected = ($current === $option['value']) ? ' selected="selected"' : '';
            $html[] = '            <option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }

        $html[] = '      </select>';
        $html[] = '      <div>' . htmlspecialchars(tinymce7Lang('tinymce7_entermode_description', 'Choose how TinyMCE 7 handles the Enter key.'), ENT_QUOTES, 'UTF-8') . '</div>';
        $html[] = '    </td>';
        $html[] = '  </tr>';
        $html[] = '</table>';

        return implode("\n", $html);
    }
}

if (!function_exists('tinymce7DetectUiLanguage')) {
    function tinymce7DetectUiLanguage(): string
    {
        $default = 'en';
        $managerLanguage = '';

        $modx = evo();
        if (is_object($modx) && isset($modx->config['manager_language'])) {
            $managerLanguage = (string)$modx->config['manager_language'];
        }

        $normalized = strtolower(trim($managerLanguage));
        if ($normalized === '') {
            return $default;
        }

        $normalized = str_replace('_', '-', $normalized);
        $normalized = preg_replace('/-(utf|utf8|utf-8)$/', '', $normalized);
        $normalized = preg_replace('/-(1251|1252|latin1|latin2|iso8859-1|iso8859-2|iso8859-5)$/', '', $normalized);
        $normalized = preg_replace('/[^a-z-]/', '', $normalized);

        $languageMap = [
            'arabic' => 'ar',
            'bulgarian' => 'bg',
            'catalan' => 'ca',
            'chinese-simplified' => 'zh_CN',
            'chinese-traditional' => 'zh_TW',
            'croatian' => 'hr',
            'czech' => 'cs',
            'danish' => 'da',
            'dutch' => 'nl',
            'english' => 'en',
            'english-british' => 'en_GB',
            'estonian' => 'et',
            'finnish' => 'fi',
            'french' => 'fr',
            'german' => 'de',
            'greek' => 'el',
            'hebrew' => 'he',
            'hungarian' => 'hu',
            'italian' => 'it',
            'japanese' => 'ja',
            'korean' => 'ko',
            'latvian' => 'lv',
            'lithuanian' => 'lt',
            'norwegian' => 'nb_NO',
            'persian' => 'fa',
            'polish' => 'pl',
            'portuguese' => 'pt_PT',
            'portuguese-br' => 'pt_BR',
            'romanian' => 'ro',
            'russian' => 'ru',
            'slovak' => 'sk',
            'slovenian' => 'sl',
            'spanish' => 'es',
            'swedish' => 'sv_SE',
            'thai' => 'th',
            'turkish' => 'tr',
            'ukrainian' => 'uk',
            'vietnamese' => 'vi',
        ];

        if (isset($languageMap[$normalized])) {
            return $languageMap[$normalized];
        }

        $base = strtok($normalized, '-');
        if ($base !== false) {
            if (isset($languageMap[$base])) {
                return $languageMap[$base];
            }

            if (strlen($base) === 2) {
                return $base;
            }
        }

        if (strpos($normalized, 'english') === 0) {
            return 'en';
        }

        return $default;
    }
}

if (!function_exists('tinymce7LanguageUrl')) {
    function tinymce7LanguageUrl(string $language): string
    {
        $language = trim($language);
        if ($language === '') {
            $language = 'en';
        }

        $languageFile = $language . '.js';
        $localPaths = [
            'assets/plugins/tinymce7/tinymce/js/tinymce/langs/' . $languageFile,
            'assets/plugins/tinymce7/langs/' . strtolower($language) . '.js',
        ];

        foreach ($localPaths as $relativePath) {
            $fullPath = MODX_BASE_PATH . $relativePath;
            if (is_file($fullPath)) {
                return MODX_BASE_URL . $relativePath;
            }
        }

        return 'https://cdn.jsdelivr.net/npm/@tinymce/tinymce-i18n@latest/langs/' . rawurlencode($language) . '.js';
    }
}

if (!function_exists('tinymce7ScriptTag')) {
    function tinymce7ScriptTag(string $url): string
    {
        $escaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return '<script src="' . $escaped . '"></script>';
    }
}

if (!function_exists('tinymce7InlineScript')) {
    function tinymce7InlineScript(string $script): string
    {
        return '<script>' . $script . '</script>';
    }
}

if (!function_exists('tinymce7McpukBrowserUrl')) {
    function tinymce7McpukBrowserUrl(): string
    {
        $managerUrl = defined('MODX_MANAGER_URL') ? MODX_MANAGER_URL : MODX_BASE_URL . 'manager/';

        return rtrim($managerUrl, '/') . '/media/browser/mcpuk/browser.php?editor=tinymce7';
    }
}

if (!function_exists('tinymce7McpukBootstrapScript')) {
    function tinymce7McpukBootstrapScript(): string
    {
        $snippets = [];
        $snippets[] = 'window.MODX_FILE_BROWSER_URL = ' . json_encode(tinymce7McpukBrowserUrl()) . ';';
        $snippets[] = 'window.MODX_BASE_URL = ' . json_encode(MODX_BASE_URL) . ';';
        if (defined('MODX_SITE_URL')) {
            $snippets[] = 'window.MODX_SITE_URL = ' . json_encode(MODX_SITE_URL) . ';';
        }

        return implode('', $snippets);
    }
}
