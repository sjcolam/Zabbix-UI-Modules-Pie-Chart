<?php
/**
 * Firmware Version Pie Chart - widget configuration form view.
 *
 * @var CView  $this
 * @var array  $data
 */

(new CWidgetFormView($data))
    ->addField(
        new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
    )
    ->addField(
        new CWidgetFieldPatternSelectHostView($data['fields']['hostids'])
    )
    ->addField(
        (new CWidgetFieldTextBoxView($data['fields']['inv_field_1']))
            ->setPlaceholder('e.g. vendor, model, type, os')
    )
    ->addField(
        (new CWidgetFieldTextBoxView($data['fields']['inv_value_1']))
            ->setPlaceholder('e.g. Tachyon, Cisco*, *PDU*')
    )
    ->addField(
        (new CWidgetFieldTextBoxView($data['fields']['inv_field_2']))
            ->setPlaceholder('e.g. vendor, model, type, os')
    )
    ->addField(
        (new CWidgetFieldTextBoxView($data['fields']['inv_value_2']))
            ->setPlaceholder('e.g. Tachyon, Cisco*, *PDU*')
    )
    ->addField(
        new CWidgetFieldRadioButtonListView($data['fields']['host_tags_evaltype'])
    )
    ->addField(
        new CWidgetFieldTagsView($data['fields']['host_tags'])
    )
    ->addField(
        new CWidgetFieldPatternSelectItemView($data['fields']['items'])
    )
    ->addField(
        new CWidgetFieldCheckBoxView($data['fields']['show_legend'])
    )
    ->show();
