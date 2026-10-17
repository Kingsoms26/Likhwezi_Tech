<?php
    // dashboardCharts.php draws the small charts on the dashboard cards as inline svg, no chart library needed
    // include it once with require_once, each function returns the html so the page decides where it goes
    // colours come from the series-* classes in dashboard.css, hovering a mark shows its value from the svg title

    // donut split into segments with a value in the middle
    // each segment is ['label' => 'New', 'value' => 3, 'series' => 'accent']
    function chartDonut(array $segments, string $centreValue, string $centreLabel): string
    {
        $total = array_sum(array_column($segments, 'value'));
        $radius = 42;
        $circumference = 2 * M_PI * $radius;
        $drawn = array_filter($segments, fn ($s) => $s['value'] > 0);
        // a small gap between segments, none when only one segment is drawn
        $gap = count($drawn) > 1 ? 2 : 0;

        $summary = implode(', ', array_map(fn ($s) => $s['label'] . ' ' . $s['value'], $segments));
        $html = '<svg class="chart-svg chart-donut" viewBox="0 0 100 100" role="img" aria-label="' . htmlspecialchars($summary) . '">';
        $html .= '<circle class="chart-track" cx="50" cy="50" r="' . $radius . '" fill="none" stroke-width="12"></circle>';

        $offset = 0;
        foreach ($drawn as $segment) {
            $length = $segment['value'] / $total * $circumference;
            $html .= sprintf(
                '<circle class="series-%s" cx="50" cy="50" r="%d" fill="none" stroke="currentColor" stroke-width="12"'
                . ' stroke-dasharray="%.2f %.2f" stroke-dashoffset="%.2f" transform="rotate(-90 50 50)"><title>%s: %d</title></circle>',
                htmlspecialchars($segment['series']), $radius,
                max(0, $length - $gap), $circumference, -$offset,
                htmlspecialchars($segment['label']), $segment['value']
            );
            $offset += $length;
        }

        $html .= '<text class="chart-centre-value" x="50" y="52" text-anchor="middle">' . htmlspecialchars($centreValue) . '</text>';
        $html .= '<text class="chart-label" x="50" y="66" text-anchor="middle">' . htmlspecialchars($centreLabel) . '</text>';

        return $html . '</svg>';
    }

    // legend with a colour swatch, label and value for each segment
    function chartLegend(array $segments, bool $inline = false): string
    {
        $html = '<ul class="chart-legend' . ($inline ? ' chart-legend-inline' : '') . '">';
        foreach ($segments as $segment) {
            $html .= '<li><span class="chart-swatch series-' . htmlspecialchars($segment['series']) . '"></span>'
                . htmlspecialchars($segment['label']) . '<b>' . (int) $segment['value'] . '</b></li>';
        }

        return $html . '</ul>';
    }

    // column chart for a count per day, each point is ['label' => '3 Oct', 'total' => 2]
    function chartColumns(array $points, string $unit = 'registration', string $series = 'accent'): string
    {
        $width = 280;
        $height = 110;
        $left = 22;
        $right = 4;
        $top = 8;
        $bottom = 20;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $max = max([1, ...array_column($points, 'total')]);
        $count = max(1, count($points));
        $step = $plotWidth / $count;

        $sum = array_sum(array_column($points, 'total'));
        $html = '<svg class="chart-svg chart-columns" viewBox="0 0 ' . $width . ' ' . $height . '" role="img"'
            . ' aria-label="' . $sum . ' ' . $unit . ($sum === 1 ? '' : 's') . ' over the last ' . $count . ' days">';

        // gridlines and labels for zero and the highest day
        foreach ([0, $max] as $value) {
            $y = $top + $plotHeight - $value / $max * $plotHeight;
            $html .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"></line>', $left, $width - $right, $y, $y);
            $html .= sprintf('<text class="chart-label" x="%d" y="%.1f" text-anchor="end">%d</text>', $left - 6, $y + 4, $value);
        }

        foreach (array_values($points) as $i => $point) {
            $x = $left + $i * $step;
            $barHeight = $point['total'] / $max * $plotHeight;
            $title = htmlspecialchars($point['label']) . ': ' . $point['total'] . ' ' . $unit . ($point['total'] === 1 ? '' : 's');

            // the full-height hit area makes empty days hoverable too
            $html .= '<g class="chart-hit"><title>' . $title . '</title>';
            $html .= sprintf('<rect x="%.1f" y="%d" width="%.1f" height="%d" fill="transparent"></rect>', $x, $top, $step, $plotHeight);
            if ($point['total'] > 0) {
                $html .= sprintf(
                    '<rect class="series-%s" x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="%.1f" fill="currentColor"></rect>',
                    htmlspecialchars($series),
                    $x + 1, $top + $plotHeight - $barHeight, $step - 2, $barHeight, min(3, ($step - 2) / 2)
                );
            }
            $html .= '</g>';
        }

        // first and last day under the axis
        if ($points) {
            $html .= '<text class="chart-label" x="' . $left . '" y="' . ($height - 4) . '">' . htmlspecialchars($points[0]['label']) . '</text>';
            $html .= '<text class="chart-label" x="' . ($width - $right) . '" y="' . ($height - 4) . '" text-anchor="end">'
                . htmlspecialchars(end($points)['label']) . '</text>';
        }

        return $html . '</svg>';
    }

    // one bar split by share, each segment is ['label' => 'Active', 'value' => 1, 'series' => 'accent']
    function chartShareBar(array $segments, string $label): string
    {
        $total = array_sum(array_column($segments, 'value'));
        $html = '<div class="chart-share" role="img" aria-label="' . htmlspecialchars($label) . '">';

        foreach ($segments as $segment) {
            if ($segment['value'] <= 0) {
                continue;
            }
            $html .= sprintf(
                '<span class="series-%s" style="width: %.2f%%;" title="%s: %d"></span>',
                htmlspecialchars($segment['series']), $segment['value'] / $total * 100,
                htmlspecialchars($segment['label']), $segment['value']
            );
        }

        return $html . '</div>';
    }

    // timeline from today to $days away with a dot for each event, the first $soonDays are shaded
    // each event needs name and daysAway
    function chartTimeline(array $events, int $days, int $soonDays): string
    {
        $width = 280;
        $height = 44;
        $inset = 6;
        $lineY = 16;
        $span = $width - 2 * $inset;
        $xFor = fn ($day) => $inset + min($day, $days) / $days * $span;

        $html = '<svg class="chart-svg chart-timeline" viewBox="0 0 ' . $width . ' ' . $height . '" role="img"'
            . ' aria-label="' . count($events) . (count($events) === 1 ? ' event' : ' events') . ' in the next ' . $days . ' days">';
        $html .= sprintf('<rect class="chart-soon" x="%d" y="%d" width="%.1f" height="12" rx="6"></rect>', $inset - 6, $lineY - 6, $xFor($soonDays) - $inset + 12);
        $html .= sprintf('<line class="chart-axis" x1="%d" x2="%d" y1="%d" y2="%d"></line>', $inset, $width - $inset, $lineY, $lineY);

        foreach ($events as $event) {
            $when = match ($event['daysAway']) {
                0       => 'today',
                1       => 'tomorrow',
                default => 'in ' . $event['daysAway'] . ' days',
            };
            $series = $event['daysAway'] <= $soonDays ? 'amber' : 'accent';
            $html .= sprintf(
                '<circle class="series-%s chart-dot" cx="%.1f" cy="%d" r="5" fill="currentColor"><title>%s, %s</title></circle>',
                $series, $xFor($event['daysAway']), $lineY, htmlspecialchars($event['name']), $when
            );
        }

        $html .= '<text class="chart-label" x="0" y="' . ($height - 4) . '">Today</text>';
        $html .= sprintf('<text class="chart-label" x="%.1f" y="%d" text-anchor="middle">%d days</text>', $xFor($soonDays), $height - 4, $soonDays);
        $html .= '<text class="chart-label" x="' . $width . '" y="' . ($height - 4) . '" text-anchor="end">' . $days . ' days</text>';

        return $html . '</svg>';
    }

    // area chart for an amount per day, each point is ['label' => '3 Oct', 'total' => 500.0]
    // $format turns an amount into text, for example formatRand
    function chartArea(array $points, callable $format, string $series = 'accent'): string
    {
        $width = 280;
        $height = 110;
        $left = 34;
        $right = 6;
        $top = 8;
        $bottom = 20;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $max = max([1, ...array_column($points, 'total')]);
        $count = count($points);
        $step = $count > 1 ? $plotWidth / ($count - 1) : $plotWidth;
        $baseline = $top + $plotHeight;

        $sum = array_sum(array_column($points, 'total'));
        $html = '<svg class="chart-svg chart-area" viewBox="0 0 ' . $width . ' ' . $height . '" role="img"'
            . ' aria-label="' . htmlspecialchars($format($sum)) . ' over the last ' . $count . ' days">';

        // gridlines and short labels for zero and the highest day, R1.5k rather than R1,500.00
        $short = fn ($v) => $v >= 1000 ? 'R' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k' : 'R' . round($v);
        foreach ([0, $max] as $value) {
            $y = $baseline - $value / $max * $plotHeight;
            $html .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"></line>', $left, $width - $right, $y, $y);
            $html .= sprintf('<text class="chart-label" x="%d" y="%.1f" text-anchor="end">%s</text>', $left - 6, $y + 4, $short($value));
        }

        if ($points) {
            $coords = [];
            foreach (array_values($points) as $i => $point) {
                $coords[] = sprintf('%.1f %.1f', $left + $i * $step, $baseline - $point['total'] / $max * $plotHeight);
            }
            $line = 'M' . implode(' L', $coords);
            $html .= '<g class="series-' . htmlspecialchars($series) . '">';
            $html .= '<path class="chart-area-fill" d="' . $line . sprintf(' L%.1f %.1f L%d %.1f Z', $left + ($count - 1) * $step, $baseline, $left, $baseline) . '" fill="currentColor"></path>';
            $html .= '<path class="chart-area-line" d="' . $line . '" fill="none" stroke="currentColor"></path>';

            // emphasise today, and a hover area for each day
            [$lastX, $lastY] = explode(' ', end($coords));
            $html .= '<circle class="chart-dot" cx="' . $lastX . '" cy="' . $lastY . '" r="4" fill="currentColor"></circle></g>';
            foreach (array_values($points) as $i => $point) {
                $html .= sprintf(
                    '<rect class="chart-hit" x="%.1f" y="%d" width="%.1f" height="%d" fill="transparent"><title>%s: %s</title></rect>',
                    $left + $i * $step - $step / 2, $top, $step, $plotHeight,
                    htmlspecialchars($point['label']), htmlspecialchars($format($point['total']))
                );
            }

            $html .= '<text class="chart-label" x="' . $left . '" y="' . ($height - 4) . '">' . htmlspecialchars($points[0]['label']) . '</text>';
            $html .= '<text class="chart-label" x="' . ($width - $right) . '" y="' . ($height - 4) . '" text-anchor="end">'
                . htmlspecialchars(end($points)['label']) . '</text>';
        }

        return $html . '</svg>';
    }

    // ring showing a percentage, with text in the middle
    function chartRing(int $percentage, string $centreValue, string $centreLabel, string $series = 'accent'): string
    {
        $percentage = max(0, min(100, $percentage));
        $radius = 40;
        $circumference = 2 * M_PI * $radius;

        $html = '<svg class="chart-svg chart-ring" viewBox="0 0 100 100" role="img" aria-label="' . $percentage . '% of the goal">';
        $html .= '<circle class="chart-ring-track" cx="50" cy="50" r="' . $radius . '" fill="none" stroke-width="10"></circle>';
        if ($percentage > 0) {
            $html .= sprintf(
                '<circle class="series-%s" cx="50" cy="50" r="%d" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"'
                . ' stroke-dasharray="%.2f %.2f" transform="rotate(-90 50 50)"><title>%d%% of the goal</title></circle>',
                htmlspecialchars($series), $radius, $percentage / 100 * $circumference, $circumference, $percentage
            );
        }
        $html .= '<text class="chart-centre-value" x="50" y="53" text-anchor="middle">' . htmlspecialchars($centreValue) . '</text>';
        $html .= '<text class="chart-label" x="50" y="67" text-anchor="middle">' . htmlspecialchars($centreLabel) . '</text>';

        return $html . '</svg>';
    }

    // small line with a soft fill and no axes, for the stat cards
    // each point is ['label' => '3 Oct', 'total' => 2], $format turns a total into text for the hover
    function chartSparkline(array $points, callable $format, string $series = 'accent'): string
    {
        $width = 280;
        $height = 52;
        $pad = 5;
        $max = max([1, ...array_column($points, 'total')]);
        $count = count($points);
        $step = $count > 1 ? ($width - 2 * $pad) / ($count - 1) : 0;
        $baseline = $height - $pad;

        $html = '<svg class="chart-svg chart-sparkline" viewBox="0 0 ' . $width . ' ' . $height . '" role="img"'
            . ' aria-label="' . ($points ? htmlspecialchars($points[0]['label'] . ' to ' . end($points)['label']) : 'No data') . '">';
        $html .= sprintf('<line class="chart-grid" x1="0" x2="%d" y1="%.1f" y2="%.1f"></line>', $width, $baseline, $baseline);

        if ($points) {
            $coords = [];
            foreach (array_values($points) as $i => $point) {
                $coords[] = sprintf('%.1f %.1f', $pad + $i * $step, $baseline - $point['total'] / $max * ($height - 2 * $pad));
            }
            $line = 'M' . implode(' L', $coords);

            $html .= '<g class="series-' . htmlspecialchars($series) . '">';
            $html .= '<path class="chart-area-fill" d="' . $line . sprintf(' L%.1f %.1f L%d %.1f Z', $pad + ($count - 1) * $step, $baseline, $pad, $baseline) . '" fill="currentColor"></path>';
            $html .= '<path class="chart-area-line" d="' . $line . '" fill="none" stroke="currentColor"></path>';
            [$lastX, $lastY] = explode(' ', end($coords));
            $html .= '<circle class="chart-dot" cx="' . $lastX . '" cy="' . $lastY . '" r="4" fill="currentColor"></circle>';
            $html .= '</g>';

            foreach (array_values($points) as $i => $point) {
                $html .= sprintf(
                    '<rect x="%.1f" y="0" width="%.1f" height="%d" fill="transparent"><title>%s: %s</title></rect>',
                    $pad + $i * $step - $step / 2, max($step, 1), $height,
                    htmlspecialchars($point['label']), htmlspecialchars($format($point['total']))
                );
            }
        }

        return $html . '</svg>';
    }
