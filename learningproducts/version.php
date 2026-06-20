<?php
/**
 * Plugin version information.
 *
 * File ini digunakan Moodle untuk:
 * - mengenali plugin
 * - membaca versi plugin
 * - menentukan compatibility Moodle
 *
 * @package    local_learningproducts
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_learningproducts';
$plugin->version   = 2026060803;
$plugin->requires  = 2024042200; // Moodle 4.4.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1';