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

        $config['language'] = $config['language'] ?? 'ja';
        $config['language_url'] = $config['language_url'] ?? tinymce7LanguageUrl($config['language']);
        $config['convert_urls'] = $config['convert_urls'] ?? false;
        $config['relative_urls'] = $config['relative_urls'] ?? false;

        $config = tinymce7ApplyToolbarPreset($config);
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
        if (isset($config['force_br_newlines']) || isset($config['force_p_newlines']) || isset($config['forced_root_block'])) {
            return $config;
        }

        $mode = tinymce7DetectEnterMode();

        switch ($mode) {
            case 'br':
                $config['force_br_newlines'] = true;
                $config['force_p_newlines'] = false;
                $config['forced_root_block'] = '';
                break;

            case 'p':
                $config['force_br_newlines'] = false;
                $config['force_p_newlines'] = true;
                $config['forced_root_block'] = 'p';
                break;
        }

        return $config;
    }
}

if (!function_exists('tinymce7DetectToolbarPreset')) {
    function tinymce7DetectToolbarPreset(): string
    {
        $keys = ['tinymce7_toolbar_preset', 'tinymce_toolbar_preset'];

        foreach ($keys as $key) {
            if (!isset(evo()->config[$key])) {
                continue;
            }

            $value = strtolower(trim((string)evo()->config[$key]));

            if ($value === 'basic') {
                return 'basic';
            }

            if ($value === 'simple') {
                return 'simple';
            }
        }

        return 'simple';
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

if (!function_exists('tinymce7ApplyToolbarPreset')) {
    function tinymce7ApplyToolbarPreset(array $config): array
    {
        $preset = tinymce7DetectToolbarPreset();

        switch ($preset) {
            case 'basic':
                $config['plugins'] = [
                    'advlist',
                    'autolink',
                    'lists',
                    'link',
                    'image',
                    'charmap',
                    'preview',
                    'anchor',
                    'searchreplace',
                    'visualblocks',
                    'code',
                    'fullscreen',
                    'insertdatetime',
                    'media',
                    'table',
                    'help',
                    'wordcount',
                ];
                $config['toolbar'] = 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help';
                if (!isset($config['height'])) {
                    $config['height'] = 500;
                }
                if (!isset($config['content_style'])) {
                    $config['content_style'] = 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }';
                }
                break;

            case 'simple':
            default:
                // Preserve the configuration file defaults.
                break;
        }

        return $config;
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
        $fieldId = 'tinymce7_entermode';
        $toolbarFieldId = 'tinymce7_toolbar_preset';

        $toolbarOptions = [
            ['value' => 'simple', 'label' => 'シンプル（既定）'],
            ['value' => 'basic', 'label' => 'Tiny Cloud 基本サンプル'],
        ];
        $options = [
            ['value' => '', 'label' => 'TinyMCEの既定（段落）'],
            ['value' => 'p', 'label' => '段落 &lt;p&gt; を挿入'],
            ['value' => 'br', 'label' => '改行 &lt;br&gt; を挿入'],
        ];

        $html = [];
        $html[] = '<style type="text/css">';
        $html[] = '    textarea.mce {';
        $html[] = '        width: 95%;';
        $html[] = '        height: 53px;';
        $html[] = '        display: block;';
        $html[] = '    }';
        $html[] = '';
        $html[] = '    #editorRow_TinyMCE7 {';
        $html[] = '        width: 99%;';
        $html[] = '    }';
        $html[] = '';
        $html[] = '    #editorRow_TinyMCE7 th {';
        $html[] = '        width: 220px;';
        $html[] = '        margin-left: 25px;';
        $html[] = '    }';
        $html[] = '';
        $html[] = '    #editorRow_TinyMCE7 td, #editorRow_TinyMCE7 th {';
        $html[] = '        border-bottom: 1px dotted #ccc;';
        $html[] = '    }';
        $html[] = '</style>';
        $html[] = '<table id="editorRow_TinyMCE7" class="settings editorRow">';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th colspan="2" style="color:#707070; background-color:#eeeeee"><h4 style="margin:3px;">TinyMCE 7</h4></th>';
        $html[] = '  </tr>';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th><label for="' . $toolbarFieldId . '">ツールバー構成</label></th>';
        $html[] = '    <td>';
        $html[] = '      <select name="' . $toolbarFieldId . '" id="' . $toolbarFieldId . '" class="inputBox">';

        foreach ($toolbarOptions as $option) {
            $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
            $label = $option['label'];
            $selected = ($toolbarPreset === $option['value']) ? ' selected="selected"' : '';
            $html[] = '            <option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }

        $html[] = '      </select>';
        $html[] = '      <div>TinyMCE 7 のツールバー構成を選択します。</div>';
        $html[] = '    </td>';
        $html[] = '  </tr>';
        $html[] = '  <tr class="row1">';
        $html[] = '    <th><label for="' . $fieldId . '">改行キーの動作</label></th>';
        $html[] = '    <td>';
        $html[] = '      <select name="' . $fieldId . '" id="' . $fieldId . '" class="inputBox">';

        foreach ($options as $option) {
            $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
            $label = $option['label'];
            $selected = ($current === $option['value']) ? ' selected="selected"' : '';
            $html[] = '            <option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }

        $html[] = '      </select>';
        $html[] = '      <div>TinyMCE 7 で Enter キーを押したときの挙動を選択します。</div>';
        $html[] = '    </td>';
        $html[] = '  </tr>';
        $html[] = '</table>';

        return implode("\n", $html);
    }
}

if (!function_exists('tinymce7LanguageUrl')) {
    function tinymce7LanguageUrl(string $language): string
    {
        $language = strtolower(trim($language));
        $localPath = MODX_BASE_PATH . 'assets/plugins/tinymce7/langs/' . $language . '.js';
        if (is_file($localPath)) {
            return MODX_BASE_URL . 'assets/plugins/tinymce7/langs/' . $language . '.js';
        }

        return 'https://cdn.jsdelivr.net/npm/@tinymce/tinymce-i18n@latest/langs/' . $language . '.js';
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
