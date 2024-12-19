<?php
function calculateDynamicPrice($base_price, $demand_factor)
{
    $surge_multiplier = 1 + ($demand_factor / 100);
    return round($base_price * $surge_multiplier, 2);
}
