<?php


namespace Picqer\BolRetailerV10\OpenApi;

class ModelGenerator
{
    protected static $propTypeMapping = [
        'array' => 'array',
        'string' => 'string',
        'boolean' => 'bool',
        'integer' => 'int',
        'float' => 'float',
        'number' => 'float'
    ];

    protected static $overrideEnumNames = [
        '>=' => 'GTE',
        '<=' => 'LTE',
        '>' => 'GT',
        '<' => 'LT',
    ];

    protected $specs;

    public function __construct()
    {
        $retailer = (new SwaggerSpecs())->load(__DIR__ . '/retailer.json')
            ->merge((new SwaggerSpecs())->load(__DIR__ . '/shared.json'));

        $this->specs = $retailer->getSpecs();
    }

    public static function run()
    {
        $generator = new static;
        $generator->generateModels();
        $generator->generateEnums();
    }

    public function generateModels(): void
    {
        foreach ($this->specs['components']['schemas'] as $type => $modelSchema) {
            $this->generateModel($type);
        }
    }

    public function generateEnums(): void
    {
        foreach ($this->specs['paths'] as $path => $methodsDef) {
            foreach ($methodsDef as $method => $methodDef) {
                foreach ($methodDef['parameters'] ?? [] as $parameterDef) {
                    if (!isset($parameterDef['schema']['enum']) || !array_values(array_filter($parameterDef['schema']['enum']))) {
                        continue;
                    }

                    $this->generateEnum(
                        ucfirst($this->kebabCaseToCamelCase($methodDef['operationId'] .'-'. $parameterDef['name'])),
                        static::$propTypeMapping[$parameterDef['schema']['type']],
                        $parameterDef['schema']['enum']
                    );
                }
            }
        }

        foreach ($this->specs['components']['schemas'] as $type => $modelSchema) {
            foreach ($modelSchema['properties'] as $property => $propertyDef) {
                if (!isset($propertyDef['enum']) || !array_values(array_filter($propertyDef['enum']))) {
                    continue;
                }

                $this->generateEnum(
                    ucfirst($this->kebabCaseToCamelCase($type .'-'. $property)),
                    static::$propTypeMapping[$propertyDef['type']],
                    $propertyDef['enum']
                );
            }
        }
    }

    public function generateModel($type): void
    {
        $modelSchema = $this->specs['components']['schemas'][$type];
        $type = $this->getType('#/components/schemas/' . $type);

        echo $type . "...";

        $code = [];
        $code[] = '<?php';
        $code[] = '';
        $code[] = sprintf('namespace %s;', $this->getModelNamespace());
        $code[] = '';
        $code[] = sprintf('use %s;', $this->getEnumNamespace());
        $code[] = '';
        $code[] = '// This class is auto generated by OpenApi\ModelGenerator';
        $code[] = sprintf('class %s extends AbstractModel', $type);
        $code[] = '{';
        $this->generateSchema($type, $modelSchema, $code);
        $this->generateFields($type, $modelSchema, $code);
        $this->generateDateTimeGetters($modelSchema, $code);
        $this->generateMonoFieldAccessors($modelSchema, $code);
        $code[] = '}';
        $code[] = '';

        file_put_contents(__DIR__ . '/../Model/' . $type . '.php', implode("\n", $code));

        echo "ok\n";
    }

    public function generateEnum($name, $type, $values): void
    {
        echo $name . "...";

        $code = [];
        $code[] = '<?php';
        $code[] = '';
        $code[] = sprintf('namespace %s;', $this->getEnumNamespace());
        $code[] = '';
        $code[] = '// This class is auto generated by OpenApi\ModelGenerator';
        $code[] = sprintf('enum %s: %s', $name, $type);
        $code[] = '{';
        $this->generateEnumFields($values, $code);
        $code[] = '}';
        $code[] = '';

        file_put_contents(__DIR__ . '/../Enum/' . $name . '.php', implode("\n", $code));

        echo "ok\n";
    }

    protected function generateSchema(string $type, array $modelSchema, array &$code): void
    {
        $code[] = '    /**';
        $code[] = '     * Returns the definition of the model: an associative array with field names as key and';
        $code[] = '     * field definition as value. The field definition contains of';
        $code[] = '     * model: Model class or null if it is a scalar type';
        $code[] = '     * array: Boolean whether it is an array';
        $code[] = '     * @return array The model definition';
        $code[] = '     */';
        $code[] = '    public function getModelDefinition(): array';
        $code[] = '    {';
        $code[] = '        return [';

        foreach ($modelSchema['properties'] as $name => $propDefinition) {
            $model = 'null';
            $enum = 'null';
            $array = 'false';

            if (isset($propDefinition['type']) && !isset($propDefinition['enum'])) {
                if ($propDefinition['type'] == 'array') {
                    $array = 'true';
                    if (isset($propDefinition['items']['$ref'])) {
                        $model = $this->getType($propDefinition['items']['$ref']) . '::class';
                    }
                }
            } elseif (isset($propDefinition['$ref'])) {
                $model = $this->getType($propDefinition['$ref']) . '::class';
            }  elseif (isset($propDefinition['enum'])) {
                $enum = 'Enum\\' . ucfirst($this->kebabCaseToCamelCase($type .'-'. $name)) . '::class';
            } else {
                // TODO create exception class for this one
                throw new \Exception('Unknown property definition');
            }

            $code[] = sprintf('            \'%s\' => [ \'model\' => %s, \'enum\' => %s, \'array\' => %s ],', $this->kebabCaseToCamelCase($name), $model, $enum, $array);
        }

        $code[] = '        ];';
        $code[] = '    }';
    }

    protected function generateFields(string $type, array $modelSchema, array &$code): void
    {
        foreach ($modelSchema['properties'] as $name => $propDefinition) {
            if (isset($propDefinition['type']) && !isset($propDefinition['enum'])) {
                $propType = static::$propTypeMapping[$propDefinition['type']];
                if ($propType == 'array' && isset($propDefinition['items']['$ref'])) {
                    $propType = $this->getType($propDefinition['items']['$ref']) . '[]';
                }
            } elseif (isset($propDefinition['$ref'])) {
                $propType = $this->getType($propDefinition['$ref']);
            } elseif (isset($propDefinition['enum'])) {
                $propType = 'Enum\\' . ucfirst($this->kebabCaseToCamelCase($type .'-'. $name));
            } else {
                // TODO create exception class for this one
                throw new \Exception('Unknown property definition');
            }

            $code[] = '';
            $code[] = '    /**';

            if (isset($propDefinition['description']) || isset($this->specs['components']['schemas'][$propType]['description'])) {
                $code[] = $this->wrapComment(sprintf('@var %s %s', $propType, $propDefinition['description'] ?? $this->specs['components']['schemas'][$propType]['description']), '     * ');
            } else {
                $code[] = sprintf('     * @var %s', $propType);
            }

            $code[] = '     */';

            if (isset($propDefinition['type']) && $propDefinition['type'] == 'array') {
                $code[] = sprintf('    public $%s = [];', $this->kebabCaseToCamelCase($name));
            } else {
                $code[] = sprintf('    public $%s;', $this->kebabCaseToCamelCase($name));
            }
        }
    }

    protected function generateEnumFields(array $values, array &$code): void
    {
        foreach ($values as $value) {
            $code[] = sprintf('    case %s = \'%s\';', $this->getEnumName($value), $value);
        }
    }

    protected function generateDateTimeGetters(array $modelSchema, array &$code): void
    {
        foreach ($modelSchema['properties'] as $name => $propDefinition) {
            if (strpos($name, 'DateTime') === false) {
                continue;
            }

            $code[] = '';
            $code[] = sprintf('    public function get%s(): ?\DateTime', ucfirst($name));
            $code[] = '    {';
            $code[] = sprintf('        if (empty($this->%s)) {', $name);
            $code[] = '            return null;';
            $code[] = '        }';
            $code[] = '';
            $code[] = sprintf('        return \DateTime::createFromFormat(\DateTime::ATOM, $this->%s);', $name);
            $code[] = '    }';
        }
    }

    protected function generateMonoFieldAccessors(array $modelSchema, array &$code): void
    {
        $monoFields = $this->getFieldsWithMonoFieldModelType($modelSchema);

        foreach ($monoFields as $fieldName => $fieldProps) {
            if ($fieldProps['monoFieldType'] == 'array') {
                continue;
            }

            $accessorName = $fieldProps['monoFieldName'];
            $accessorFullName = $accessorName;

            if (strpos(strtolower($accessorName), substr(strtolower($fieldName), 0, -1)) === false) {
                $accessorFullName = $fieldName . ucfirst($accessorName);
            }

            if (isset(static::$propTypeMapping[$fieldProps['monoFieldType']])) {
                $accessorTypePhp = static::$propTypeMapping[$fieldProps['monoFieldType']];
            } else {
                $accessorTypePhp = $fieldProps['monoFieldType'];
            }
            $accessorTypeDoc = $accessorTypePhp;

            $code[] = '';

            if ($fieldProps['array']) {
                $code[] = '    /**';
                $code[] = sprintf('     * Returns an array with the %ss from %s.', $accessorName, $fieldName);
                $code[] = sprintf('     * @return %s[] %ss from %s.', $accessorTypeDoc, ucfirst($accessorName), $fieldName);
                $code[] = '     */';
                $code[] = sprintf('    public function get%ss(): array', ucfirst($accessorFullName));
                $code[] = '    {';
                $code[] = '        return array_map(function ($model) {';
                $code[] = sprintf('            return $model->%s;', $fieldProps['monoFieldName']);
                $code[] = sprintf('        }, $this->%s);', $fieldName);
                $code[] = '    }';
            } else {
                $code[] = '    /**';
                $code[] = sprintf('     * Returns %s from %s.', $accessorName, $fieldName);
                $code[] = sprintf('     * @return %s %s from %s.', $accessorTypeDoc, ucfirst($accessorName), $fieldName);
                $code[] = '     */';
                $code[] = sprintf('    public function get%s(): %s', ucfirst($accessorFullName), $accessorTypePhp);
                $code[] = '    {';
                $code[] = sprintf('        return $this->%s->%s;', $fieldName, $fieldProps['monoFieldName']);
                $code[] = '    }';
            }

            $code[] = '';

            if ($fieldProps['array']) {
                $code[] = '    /**';
                $code[] = sprintf('     * Sets %s by an array of %ss.', $fieldName, $accessorName);
                $code[] = sprintf('     * @param %s[] $%ss %ss for %s.', $accessorTypeDoc, $accessorName, ucfirst($accessorName), $fieldName);
                $code[] = '     */';
                $code[] = sprintf('    public function set%ss(array $%ss): void', ucfirst($accessorFullName), $accessorName);
                $code[] = '    {';
                $code[] = sprintf('        $this->%s = array_map(function ($%s) {', $fieldName, $fieldProps['monoFieldName']);
                $code[] = sprintf('            return %s::constructFromArray([\'%s\' => $%s]);', $fieldProps['fieldType'], $fieldProps['monoFieldName'], $fieldProps['monoFieldName']);
                $code[] = sprintf('        }, $%ss);', $accessorName);
                $code[] = '    }';
            } else {
                $code[] = '    /**';
                $code[] = sprintf('     * Sets %s by %s.', $fieldName, $accessorName);
                $code[] = sprintf('     * @param %s $%s %s for %s.', $accessorTypeDoc, $accessorName, ucfirst($accessorName), $fieldName);
                $code[] = '     */';
                $code[] = sprintf('    public function set%s(%s $%s): void', ucfirst($accessorFullName), $accessorTypePhp, $accessorName);
                $code[] = '    {';
                $code[] = sprintf('        $this->%s = %s::constructFromArray([\'%s\' => $%s]);', $fieldName, $fieldProps['fieldType'], $fieldProps['monoFieldName'], $fieldProps['monoFieldName']);
                $code[] = '    }';
            }

            if ($fieldProps['array']) {
                $code[] = '';
                $code[] = '    /**';
                $code[] = sprintf('     * Adds a new %s to %s by %s.', $fieldProps['fieldType'], $fieldName, $accessorName);
                $code[] = sprintf('     * @param %s $%s %s for the %s to add.', $accessorTypeDoc, $accessorName, ucfirst($accessorName), $fieldProps['fieldType']);
                $code[] = '     */';
                $code[] = sprintf('    public function add%s(%s $%s): void', ucfirst($accessorFullName), $accessorTypePhp, $accessorName);
                $code[] = '    {';
                $code[] = sprintf('        $this->%s[] = %s::constructFromArray([\'%s\' => $%s]);', $fieldName, $fieldProps['fieldType'], $fieldProps['monoFieldName'], $fieldProps['monoFieldName']);
                $code[] = '    }';
            }
        }
    }



    protected function getType(string $ref): string
    {
        //strip #/components/schemas/
        $type = substr($ref, strrpos($ref, '/') + 1);

        // There are some weird types like 'delivery windows for inbound shipments.', uppercase and concat
        $type = str_replace(['.', ','], '', $type);
        $words = explode(' ', $type);
        $words = array_map(function ($word) {
            return ucfirst($word);
        }, $words);
        $type = implode('', $words);

        // Classname 'Return' is not allowed in php <= 7
        if ($type == 'Return') {
            $type = 'ReturnObject';
        }

        return $type;
    }

    protected function getModelNamespace(): string
    {
        $namespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\'));
        return $namespace . '\Model';
    }

    protected function getEnumNamespace(): string
    {
        $namespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\'));
        return $namespace . '\Enum';
    }

    protected function getFieldsWithMonoFieldModelType(array $modelSchema): array
    {
        $fields = [];

        foreach ($modelSchema['properties'] as $propName => $propDefinition) {
            $isArray = null;
            if (isset($propDefinition['$ref'])) {
                $propType = $this->getType($propDefinition['$ref']);
                $isArray = false;
            } elseif (isset($propDefinition['items']['$ref'])) {
                $propType = $this->getType($propDefinition['items']['$ref']);
                $isArray = true;
            } else {
                $propType = $propDefinition['type'];
            }

            if (!isset($this->specs['components']['schemas'][$propType])) {
                continue;
            }

            if (count($this->specs['components']['schemas'][$propType]['properties']) != 1) {
                continue;
            }

            $subPropName = array_keys($this->specs['components']['schemas'][$propType]['properties'])[0];

            $subProp = $this->specs['components']['schemas'][$propType]['properties'][$subPropName];
            if (isset($subProp['type'])) {
                $subPropType = $subProp['type'];
            } elseif (isset($subProp['$ref'])) {
                $subPropType = $this->getType($subProp['$ref']);
            } else {
                throw new \Exception('Unknown sub property type');
            }

            $fields[$propName] = [
                'fieldType' => $propType,
                'monoFieldName' => $subPropName,
                'monoFieldType' => $subPropType,
                'array' => $isArray,
            ];
        }

        return $fields;
    }

    protected function kebabCaseToCamelCase(string $name): string
    {
        // Fix for bug in specs where name contains spaces (e.g. 'get packing list')
        $name = str_replace(' ', '-', $name);

        $nameElems = explode('-', $name);
        for ($i=1; $i<count($nameElems); $i++) {
            $nameElems[$i] = ucfirst($nameElems[$i]);
        }
        return implode('', $nameElems);
    }

    protected function getEnumName(string $name): string
    {
        $name = preg_replace('/[\-\s\/]+/', '_', $name);

        if (isset(static::$overrideEnumNames[$name])) {
            $name = static::$overrideEnumNames[$name];
        }

        // We add the first `_` for enums starting with a integer character
        $prefix = is_numeric($name[0]) ? '_' : '';

        return $prefix.strtoupper($name);
    }

    protected function wrapComment(string $comment, string $linePrefix, int $maxLength = 120): string
    {
        $wordWrapped = wordwrap(strip_tags($comment), $maxLength - strlen($linePrefix));
        return $linePrefix . trim(str_replace("\n", "\n{$linePrefix}", $wordWrapped));
    }
}
