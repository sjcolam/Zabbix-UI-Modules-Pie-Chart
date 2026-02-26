<?php
namespace Modules\FirmwarePieChart\Includes;

use Zabbix\Widgets\{
    CWidgetField,
    CWidgetForm,
    Fields\CWidgetFieldMultiSelectGroup,
    Fields\CWidgetFieldPatternSelectHost,
    Fields\CWidgetFieldPatternSelectItem,
    Fields\CWidgetFieldCheckBox,
    Fields\CWidgetFieldTextBox,
    Fields\CWidgetFieldRadioButtonList,
    Fields\CWidgetFieldTags
};

class WidgetForm extends CWidgetForm {

    public function addFields(): self {
        return $this
            ->addField(
                new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
            )
            ->addField(
                new CWidgetFieldPatternSelectHost('hostids', _('Hosts'))
            )
            ->addField(
                (new CWidgetFieldTextBox('inv_field_1', _('Inventory field 1')))
                    ->setDefault('')
            )
            ->addField(
                (new CWidgetFieldTextBox('inv_value_1', _('Inventory value 1')))
                    ->setDefault('')
            )
            ->addField(
                (new CWidgetFieldTextBox('inv_field_2', _('Inventory field 2')))
                    ->setDefault('')
            )
            ->addField(
                (new CWidgetFieldTextBox('inv_value_2', _('Inventory value 2')))
                    ->setDefault('')
            )
            ->addField(
                (new CWidgetFieldRadioButtonList('host_tags_evaltype', _('Host tags'), [
                    TAG_EVAL_TYPE_AND_OR => _('And/Or'),
                    TAG_EVAL_TYPE_OR     => _('Or')
                ]))->setDefault(TAG_EVAL_TYPE_AND_OR)
            )
            ->addField(
                new CWidgetFieldTags('host_tags')
            )
            ->addField(
                (new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldCheckBox('show_legend', _('Show legend')))
                    ->setDefault(1)
            );
    }
}
