<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace MASK\Mask\CodeGenerator;

use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Utility\GeneralUtility as MaskUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates the html and fluid for mask content elements
 */
class HtmlCodeGenerator
{
    /**
     * StorageRepository
     *
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var int
     */
    protected $indent = 4;

    /**
     * @param StorageRepository $storageRepository
     */
    public function __construct(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * Generates Fluid HTML for Contentelements
     *
     * @param string $key
     * @param string $table
     * @return string $html
     * @author Gernot Ploiner <gp@webprofil.at>
     */
    public function generateHtml($key, $table): string
    {
        $storage = $this->storageRepository->loadElement($table, $key);
        $html = [];
        foreach ($storage['tca'] ?? [] as $fieldKey => $fieldConfig) {
            $part = $this->generateFieldHtml($fieldKey, $key, $table);
            if ($part !== '') {
                $html[] = $part;
            }
        }
        return implode("\n", $html) . "\n";
    }

    /**
     * Generates HTML for a field
     * @param string $fieldKey
     * @param string $elementKey
     * @param string $table
     * @param string $datafield
     * @param int $depth
     * @return string
     * @author Gernot Ploiner <gp@webprofil.at>
     */
    protected function generateFieldHtml($fieldKey, $elementKey, $table, $datafield = 'data', $depth = 0): string
    {
        $html = [];
        $formType = $this->storageRepository->getFormType($fieldKey, $elementKey, $table);
        if (in_array($formType, ['Tab', 'Linebreak'])) {
            return '';
        }
        switch ($formType) {
            case 'Select':
                if (($GLOBALS['TCA'][$table]['columns'][$fieldKey]['config']['foreign_table'] ?? '') !== '') {
                    $html[] =  $this->drawWhitespace(0 + $depth) . '<f:for each="{' . $datafield . '.' . $fieldKey . '_items}" as="' . $datafield . '_item' . '">';
                    $html[] = $this->drawWhitespace(1 + $depth) . '<div>{' . $datafield . '_item.uid}' . '</div>';
                    $html[] = $this->drawWhitespace(0 + $depth) . '</f:for>';
                } else {
                    $html[] = $this->drawWhitespace(0 + $depth) . $this->getVariable($datafield, $fieldKey);
                }
                break;
            case 'Radio':
            case 'Check':
                $html[] = $this->drawWhitespace(0 + $depth) . $this->getVariable($datafield, $fieldKey);
                break;
            case 'Content':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:for each="{' . $datafield . '.' . $fieldKey . '}" as="' . $datafield . '_item' . '">';
                $html[] = $this->drawWhitespace(2 + $depth) . '<f:cObject typoscriptObjectPath="lib.tx_mask.content">{' . $datafield . '_item.uid}</f:cObject>';
                $html[] = $this->drawWhitespace(1 + $depth) . '</f:for>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Date':
            case 'Timestamp':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:format.date format="d.m.Y">{' . $datafield . '.' . $fieldKey . '}</f:format.date>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Datetime':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:format.date format="d.m.Y - H:i:s">{' . $datafield . '.' . $fieldKey . '}</f:format.date>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'File':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:for each="{' . $datafield . '.' . $fieldKey . '}" as="file">';
                $html[] = $this->drawWhitespace(2 + $depth) . '<f:image image="{file}" alt="{file.alternative}" title="{file.title}" width="200" />';
                $html[] = $this->drawWhitespace(2 + $depth) . '{file.description}';
                $html[] = $this->drawWhitespace(1 + $depth) . '</f:for>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Float':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:format.number decimals="2" decimalSeparator="," thousandsSeparator=".">{' . $datafield . '.' . $fieldKey . '}</f:format.number>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Inline':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<ul>';
                $html[] = $this->drawWhitespace(2 + $depth) . '<f:for each="{' . $datafield . '.' . $fieldKey . '}" as="' . $datafield . '_item' . '">';
                $html[] = $this->drawWhitespace(3 + $depth) . '<li>';
                $inlineFields = $this->storageRepository->loadInlineFields($fieldKey);
                if ($inlineFields) {
                    foreach ($inlineFields as $inlineField) {
                        $html[] = $this->generateFieldHtml($inlineField['maskKey'], $elementKey, $fieldKey, $datafield . '_item', 4 + $depth);
                    }
                }
                $html[] = $this->drawWhitespace(3 + $depth) . '</li>';
                $html[] = $this->drawWhitespace(2 + $depth) . '</f:for>';
                $html[] = $this->drawWhitespace(1 + $depth) . '</ul>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Palette':
                $paletteFields = $this->storageRepository->loadInlineFields($fieldKey, $elementKey);
                foreach ($paletteFields ?? [] as $paletteField) {
                    $part = $this->generateFieldHtml(($paletteField['coreField'] ?? false) ? $paletteField['key'] : $paletteField['maskKey'], $elementKey, $table, $datafield, $depth);
                    if ($part !== '') {
                        $html[] = $part;
                    }
                }
                break;
            case 'Group':
                if (($GLOBALS['TCA'][$table]['columns'][$fieldKey]['config']['internal_type'] ?? '') === 'db') {
                    $html[] = $this->drawWhitespace(0 + $depth) . '<f:for each="{' . $datafield . '.' . $fieldKey . '_items}" as="' . $datafield . '_item' . '">';
                    $html[] = $this->drawWhitespace(1 + $depth) . '<div>{' . $datafield . '_item.uid}' . '</div>';
                    $html[] = $this->drawWhitespace(0 + $depth) . '</f:for>';
                    break;
                }
                // no break intended.
            case 'String':
            case 'Integer':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '{' . $datafield . '.' . $fieldKey . '}';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Link':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:link.typolink parameter="{' . $datafield . '.' . $fieldKey . '}"></f:link.typolink>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Richtext':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:format.html>{' . $datafield . '.' . $fieldKey . '}</f:format.html>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
            case 'Text':
                $html[] = $this->drawWhitespace(0 + $depth) . '<f:if condition="{' . $datafield . '.' . $fieldKey . '}">';
                $html[] = $this->drawWhitespace(1 + $depth) . '<f:format.nl2br>{' . $datafield . '.' . $fieldKey . '}</f:format.nl2br>';
                $html[] = $this->drawWhitespace(0 + $depth) . '</f:if>';
                break;
        }
        return implode("\n", $html);
    }

    /**
     * @param $fieldKey
     * @param $datafield
     * @return string
     */
    protected function getVariable($datafield, $fieldKey)
    {
        $key = MaskUtility::isMaskIrreTable($fieldKey) ? MaskUtility::removeMaskPrefix($fieldKey) : $fieldKey;
        return '<f:variable name="' . GeneralUtility::underscoredToLowerCamelCase($key) . '" value="{' . $datafield . '.' . $fieldKey . '}"/>';
    }

    /**
     * @param int $times
     * @return string
     */
    protected function drawWhitespace($times = 1)
    {
        return str_repeat(' ', $this->indent * $times);
    }
}
