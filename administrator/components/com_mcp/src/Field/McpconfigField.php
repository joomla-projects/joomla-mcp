<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * MCP Config Field
 *
 * @since  __DEPLOY_VERSION__
 */
class McpconfigField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Mcpconfig';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getInput()
    {
        // Get the client token from the form
        $form        = $this->form;
        $clientToken = $form->getValue('client_token');
        $clientName  = $form->getValue('client_name');

        if (empty($clientToken)) {
            return '<div class="alert alert-info">' .
                   '<span class="icon-info-circle" aria-hidden="true"></span> ' .
                   Text::_('COM_MCP_FIELD_MCP_CONFIG_SAVE_FIRST') .
                   '</div>';
        }

        // Get subtype attribute
        $subtype = (string) $this->element['subtype'];

        // Get base URL
        $baseUrl = rtrim(Uri::root(), '/');

        // Extract hostname from URL for server name
        $parsedUrl = parse_url($baseUrl);
        $hostname  = $parsedUrl['host'] ?? 'localhost';

        // Sanitize hostname: replace dots and other invalid chars with hyphens, convert to lowercase
        // MCP names can only contain letters, numbers, hyphens, and underscores
        $sanitizedHostname = strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $hostname));

        // Build server name from hostname and client name
        // e.g., "joomla-mcp-dev-test-myclient"
        $serverName = $sanitizedHostname;
        if (!empty($clientName)) {
            // Sanitize client name for use in server name (remove special chars, spaces, etc.)
            $sanitizedClientName = strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $clientName));
            $serverName          = $sanitizedHostname . '-' . $sanitizedClientName;
        }

        // Build configuration array
        $config = [
            'mcpServers' => [
                $serverName => [
                    'type'    => 'streamable-http',
                    'url'     => $baseUrl . '/api/index.php/v1/mcp',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $clientToken,
                    ],
                    'note' => 'For Streamable HTTP connections, add this URL directly in your MCP Client',
                ],
            ],
        ];

        // Convert to formatted JSON
        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Build Claude Code CLI command
        $claudeCodeCommand = \sprintf(
            'claude mcp add --transport http %s %s --header "Authorization: Bearer %s"',
            $serverName,
            $baseUrl . '/api/index.php/v1/mcp',
            $clientToken
        );

        // Build HTML output based on subtype
        $html           = [];
        $copyButtonText = Text::_('COM_MCP_FIELD_MCP_CONFIG_COPY_BUTTON');
        $copiedText     = Text::_('COM_MCP_FIELD_MCP_CONFIG_COPIED');

        if ($subtype === 'claudecode') {
            // Claude Code CLI command section
            $copyCommandText = Text::_('COM_MCP_FIELD_MCP_CONFIG_COPY_COMMAND');
            $html[]          = '<div class="input-group">';
            $html[]          = '  <input type="text" readonly class="form-control font-monospace" value="' . htmlspecialchars($claudeCodeCommand, ENT_COMPAT, 'UTF-8') . '">';
            $html[]          = '  <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); const orig = this.innerHTML; this.innerHTML=\'<span class=&quot;icon-check&quot;></span> ' . $copiedText . '\'; setTimeout(() => this.innerHTML=orig, 2000);">';
            $html[]          = '    <span class="icon-copy" aria-hidden="true"></span> ' . $copyCommandText;
            $html[]          = '  </button>';
            $html[]          = '</div>';
        } elseif ($subtype === 'fullconfig') {
            // Full JSON configuration section
            $html[] = '<textarea readonly class="form-control font-monospace" rows="12" style="resize: vertical;">' . htmlspecialchars($jsonConfig, ENT_COMPAT, 'UTF-8') . '</textarea>';
            $html[] = '<button type="button" class="btn btn-sm btn-secondary mt-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.textContent=\'' . $copiedText . '\'; setTimeout(() => this.textContent=\'' . $copyButtonText . '\', 2000);">';
            $html[] = '  <span class="icon-copy" aria-hidden="true"></span> ' . $copyButtonText;
            $html[] = '</button>';
        }

        return implode("\n", $html);
    }

    /**
     * Method to get the field label markup.
     *
     * @return  string  The field label markup.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getLabel()
    {
        // Use default label rendering
        return parent::getLabel();
    }
}
