<?php
namespace CMB\Fields;

use CMB\Core\Contracts\Abstracts\AbstractField;
use CMB\Core\FieldRenderer;

class GroupField extends AbstractField {
    public function render(): string {
        $groupFields = $this->config['fields'] ?? [];
        $value = get_post_meta(get_the_ID(), $this->getId()) ?: [];




        $output = '<div class="cmb-group">';
        $output .= '<div class="cmb-group-items">';
        if (empty($value)) {
            $output .= $this->renderGroupItem($groupFields, 0);
        } else {
            foreach ($value as $index => $groupItem) {
                $output .= $this->renderGroupItem($groupFields, $index, $groupItem);
            }
        }

        $output .= '</div>'; // .cmb-group-items

        $output .= '</div>'; // .cmb-group-wrapper

        return $output;
    }

    protected function renderGroupItem(array $groupFields, int $index, array $groupItem = []): string {
        $groupLabel = $this->getLabel();

        $output = '<div class="cmb-group-item">';
        $output .= '<div class="cmb-group-item-header">'.$groupLabel.'</div>';
        $output .= '<div class="cmb-group-item-body">';
        $output .= '<div class="cmb-group-index">0</div>';
        $output .= '<div class="cmb-group-fields">';
        foreach ($groupFields as $field) {


    
                $renderer = new FieldRenderer(get_post(get_the_ID()));
                $output .= $renderer->render($field);
            
        }
        $output .= '</div>';
        $output .= '<span class="cmb-remove-row">×</span>';

        $output .= '</div>';

        $output .= '</div>';
        return $output;
    }

    public function sanitize($value) {
        return is_array($value) ? array_map('sanitize_text_field', $value) : [];
    }
}