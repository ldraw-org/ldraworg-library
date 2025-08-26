import ace from 'ace-builds/src-noconflict/ace';

// Ace Worker files

// PHP Mode
import modePhpUrl from 'ace-builds/src-noconflict/mode-php?url';
ace.config.setModuleUrl('ace/mode/php', modePhpUrl);
import modePhpLaravelBladeUrl from 'ace-builds/src-noconflict/mode-php_laravel_blade?url';
ace.config.setModuleUrl('ace/mode/php_laravel_blade', modePhpLaravelBladeUrl);
import workerPhpUrl from 'ace-builds/src-noconflict/worker-php?url';
ace.config.setModuleUrl('ace/mode/php_worker', workerPhpUrl);

// HTML Mode
import modeHtmlUrl from 'ace-builds/src-noconflict/mode-html?url';
ace.config.setModuleUrl('ace/mode/html', modeHtmlUrl);
import workerHtmlUrl from 'ace-builds/src-noconflict/worker-html?url';
ace.config.setModuleUrl('ace/mode/html_worker', workerHtmlUrl);

// Css Mode
import modeCssUrl from 'ace-builds/src-noconflict/mode-css?url';
ace.config.setModuleUrl('ace/mode/css', modeCssUrl);
import workerCssUrl from 'ace-builds/src-noconflict/worker-css?url';
ace.config.setModuleUrl('ace/mode/css_worker', workerCssUrl);

// Javascript Mode
import modeJavascriptUrl from 'ace-builds/src-noconflict/mode-javascript?url';
ace.config.setModuleUrl('ace/mode/javascript', modeJavascriptUrl);
import workerJavascriptUrl from 'ace-builds/src-noconflict/worker-javascript?url';
ace.config.setModuleUrl('ace/mode/javascript_worker', workerJavascriptUrl);

// JSON Mode
import modeJsonUrl from 'ace-builds/src-noconflict/mode-json?url';
ace.config.setModuleUrl('ace/mode/json', modeJsonUrl);
import workerJsonUrl from 'ace-builds/src-noconflict/worker-json?url';
ace.config.setModuleUrl('ace/mode/json_worker', workerJsonUrl);

// Markdown Mode
import modeMarkdownUrl from 'ace-builds/src-noconflict/mode-markdown?url';
ace.config.setModuleUrl('ace/mode/markdown', modeMarkdownUrl);

// Text Mode
import modeTextUrl from 'ace-builds/src-noconflict/mode-text?url';
ace.config.setModuleUrl('ace/mode/text', modeTextUrl);

// Editor Theme
import themeMonokai from 'ace-builds/src-noconflict/theme-monokai?url';
ace.config.setModuleUrl('ace/theme/monokai', themeMonokai);

// Search Box
import extSearchboxUrl from 'ace-builds/src-noconflict/ext-searchbox?url';
ace.config.setModuleUrl('ace/ext/searchbox', extSearchboxUrl);

window.ace = ace;
