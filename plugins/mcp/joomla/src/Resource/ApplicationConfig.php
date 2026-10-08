<?php

namespace Joomla\Plugin\Mcp\Joomla\Resource;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mcp\Resource\ResourceInterface;
use Joomla\CMS\Mcp\Resource\ResourceResult;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\Component\Config\Api\Resource\ApplicationConfig as ConfigDTO;

class ApplicationConfig implements ResourceInterface
{
    public function getName(): string
    {
        return "applicationConfig";
    }

    public function getUri(): string
    {
        return "joomla://com_config/application";
    }

    public function getDescription(): string
    {
        return Text::_('PLG_MCP_JOOMLA_APPLICATION_CONFIG_DESC');
    }

    public function getTitle(): string
    {
        return Text::_('PLG_MCP_JOOMLA_APPLICATION_CONFIG_TITLE');
    }

    public function getMimeType(): string
    {
        return "application/json";
    }

    /**
     * Get allowed field names from DTO using reflection (whitelist approach)
     * Only returns fields that are defined in the DTO and NOT marked as #[Guarded]
     */
    private function getAllowedFields(): array
    {
        $reflection    = new \ReflectionClass(ConfigDTO::class);
        $allowedFields = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Guarded::class);
            if (empty($attributes)) {
                $allowedFields[] = $property->getName();
            }
        }

        return $allowedFields;
    }

    public function read(): ResourceResult
    {
        $app = Factory::getApplication();

        $model = $app->bootComponent('com_config')
            ->getMVCFactory()
            ->createModel('Application', 'Administrator', ['ignore_request' => true]);

        $data = $model->getData();

        // Whitelist approach: only include fields defined in DTO without #[Guarded]
        $allowedFields = $this->getAllowedFields();
        $filteredData  = [];

        foreach ($allowedFields as $field) {
            if (\array_key_exists($field, $data)) {
                $filteredData[$field] = $data[$field];
            }
        }

        // Format as JSON
        $jsonData = json_encode($filteredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return ResourceResult::text($this->getUri(), $jsonData, $this->getMimeType());
    }
}
