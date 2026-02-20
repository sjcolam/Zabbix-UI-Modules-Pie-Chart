<?php declare(strict_types = 0);
/**
 * Firmware Pie Chart widget view.
 *
 * @var CView $this
 * @var array $data
 */

$payload = json_encode([
    'firmware_counts' => $data['firmware_counts'],
    'show_legend'     => $data['show_legend'],
    'error'           => $data['error'],
]);

if ($data['error'] !== null) {
    $body = (new CDiv(
        (new CDiv(htmlspecialchars($data['error'])))->addClass('fw-pie-error')
    ))->addClass('fw-pie-widget')->setAttribute('data-pie', $payload);
}
else {
    $body = (new CDiv([
        (new CDiv(
            (new CTag('canvas', true))->addClass('fw-pie-canvas')
        ))->addClass('fw-pie-wrap'),
        $data['show_legend'] ? (new CDiv())->addClass('fw-pie-legend') : null,
    ]))->addClass('fw-pie-widget')->setAttribute('data-pie', $payload);
}

(new CWidgetView($data))
    ->addItem($body)
    ->show();
