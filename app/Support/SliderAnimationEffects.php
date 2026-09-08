<?php

namespace App\Support;

class SliderAnimationEffects
{
    public static function groupedOptions(): array
    {
        return [
            [
                'label' => 'Fade',
                'options' => [
                    ['label' => 'Fade In', 'value' => 'animate__fadeIn'],
                    ['label' => 'Fade In Down', 'value' => 'animate__fadeInDown'],
                    ['label' => 'Fade In Down Big', 'value' => 'animate__fadeInDownBig'],
                    ['label' => 'Fade In Left', 'value' => 'animate__fadeInLeft'],
                    ['label' => 'Fade In Left Big', 'value' => 'animate__fadeInLeftBig'],
                    ['label' => 'Fade In Right', 'value' => 'animate__fadeInRight'],
                    ['label' => 'Fade In Right Big', 'value' => 'animate__fadeInRightBig'],
                    ['label' => 'Fade In Up', 'value' => 'animate__fadeInUp'],
                    ['label' => 'Fade In Up Big', 'value' => 'animate__fadeInUpBig'],
                    ['label' => 'Fade In Top Left', 'value' => 'animate__fadeInTopLeft'],
                    ['label' => 'Fade In Top Right', 'value' => 'animate__fadeInTopRight'],
                    ['label' => 'Fade In Bottom Left', 'value' => 'animate__fadeInBottomLeft'],
                    ['label' => 'Fade In Bottom Right', 'value' => 'animate__fadeInBottomRight'],
                ],
            ],
            [
                'label' => 'Zoom',
                'options' => [
                    ['label' => 'Zoom In', 'value' => 'animate__zoomIn'],
                    ['label' => 'Zoom In Down', 'value' => 'animate__zoomInDown'],
                    ['label' => 'Zoom In Left', 'value' => 'animate__zoomInLeft'],
                    ['label' => 'Zoom In Right', 'value' => 'animate__zoomInRight'],
                    ['label' => 'Zoom In Up', 'value' => 'animate__zoomInUp'],
                ],
            ],
            [
                'label' => 'Slide',
                'options' => [
                    ['label' => 'Slide In Down', 'value' => 'animate__slideInDown'],
                    ['label' => 'Slide In Left', 'value' => 'animate__slideInLeft'],
                    ['label' => 'Slide In Right', 'value' => 'animate__slideInRight'],
                    ['label' => 'Slide In Up', 'value' => 'animate__slideInUp'],
                ],
            ],
            [
                'label' => 'Bounce',
                'options' => [
                    ['label' => 'Bounce In', 'value' => 'animate__bounceIn'],
                    ['label' => 'Bounce In Down', 'value' => 'animate__bounceInDown'],
                    ['label' => 'Bounce In Left', 'value' => 'animate__bounceInLeft'],
                    ['label' => 'Bounce In Right', 'value' => 'animate__bounceInRight'],
                    ['label' => 'Bounce In Up', 'value' => 'animate__bounceInUp'],
                ],
            ],
            [
                'label' => 'Back',
                'options' => [
                    ['label' => 'Back In Down', 'value' => 'animate__backInDown'],
                    ['label' => 'Back In Left', 'value' => 'animate__backInLeft'],
                    ['label' => 'Back In Right', 'value' => 'animate__backInRight'],
                    ['label' => 'Back In Up', 'value' => 'animate__backInUp'],
                ],
            ],
            [
                'label' => 'Rotate',
                'options' => [
                    ['label' => 'Rotate In', 'value' => 'animate__rotateIn'],
                    ['label' => 'Rotate In Down Left', 'value' => 'animate__rotateInDownLeft'],
                    ['label' => 'Rotate In Down Right', 'value' => 'animate__rotateInDownRight'],
                    ['label' => 'Rotate In Up Left', 'value' => 'animate__rotateInUpLeft'],
                    ['label' => 'Rotate In Up Right', 'value' => 'animate__rotateInUpRight'],
                ],
            ],
            [
                'label' => 'Attention',
                'options' => [
                    ['label' => 'Bounce', 'value' => 'animate__bounce'],
                    ['label' => 'Flash', 'value' => 'animate__flash'],
                    ['label' => 'Pulse', 'value' => 'animate__pulse'],
                    ['label' => 'Rubber Band', 'value' => 'animate__rubberBand'],
                    ['label' => 'Shake X', 'value' => 'animate__shakeX'],
                    ['label' => 'Shake Y', 'value' => 'animate__shakeY'],
                    ['label' => 'Head Shake', 'value' => 'animate__headShake'],
                    ['label' => 'Swing', 'value' => 'animate__swing'],
                    ['label' => 'Tada', 'value' => 'animate__tada'],
                    ['label' => 'Wobble', 'value' => 'animate__wobble'],
                    ['label' => 'Jello', 'value' => 'animate__jello'],
                    ['label' => 'Heart Beat', 'value' => 'animate__heartBeat'],
                ],
            ],
            [
                'label' => 'Flip',
                'options' => [
                    ['label' => 'Flip', 'value' => 'animate__flip'],
                    ['label' => 'Flip In X', 'value' => 'animate__flipInX'],
                    ['label' => 'Flip In Y', 'value' => 'animate__flipInY'],
                ],
            ],
            [
                'label' => 'LightSpeed',
                'options' => [
                    ['label' => 'Light Speed In Left', 'value' => 'animate__lightSpeedInLeft'],
                    ['label' => 'Light Speed In Right', 'value' => 'animate__lightSpeedInRight'],
                ],
            ],
            [
                'label' => 'Special',
                'options' => [
                    ['label' => 'Jack In The Box', 'value' => 'animate__jackInTheBox'],
                    ['label' => 'Roll In', 'value' => 'animate__rollIn'],
                ],
            ],
        ];
    }

    public static function values(): array
    {
        return collect(static::groupedOptions())
            ->flatMap(fn (array $group) => $group['options'] ?? [])
            ->pluck('value')
            ->values()
            ->all();
    }
}
