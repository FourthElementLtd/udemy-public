<?php
/**
 * Dynamic CSS styles for the Geo Prompt.
 *
 * @since 1.0.0
 *
 * @var int    $border_radius
 * @var string $bg_color
 * @var string $accent_color
 * @var string $font_color
 */

defined( 'ABSPATH' ) || die;

?>
<style type="text/css">

	.featherlight .featherlight-content, .featherlight .featherlight-inner, .featherlight .geo-wrapper,
	.featherlight .geo-wrapper .select2-container--default .select2-selection--single,
	.featherlight .geo-wrapper .select2-dropdown, .featherlight .geo-wrapper input, .featherlight .geo-wrapper button {
		border-radius: <?php echo absint( $border_radius ) ?>px;
	}

	.featherlight .featherlight-content, .featherlight .featherlight-inner, .featherlight .geo-wrapper .select2-dropdown{
		background-color: <?php echo esc_attr( $bg_color ) ?>;
	}

	.featherlight .geo-wrapper h2, .featherlight .geo-wrapper h3, .featherlight .geo-wrapper abbr,
	.featherlight .geo-wrapper strong, .featherlight .geo-wrapper a, .featherlight .geo-wrapper input[type=checkbox] {
		color: <?php echo esc_attr( $accent_color ) ?>;
	}

	.featherlight .featherlight-close-icon, .featherlight .geo-wrapper, .featherlight .geo-wrapper input,
	.featherlight .geo-wrapper .select2-container--default .select2-selection--single .select2-selection__rendered,
	.featherlight .geo-wrapper .select2-container--default, .featherlight .geo-wrapper input, .featherlight .geo-wrapper input:focus {
		color: <?php echo esc_attr( $font_color ) ?>;
	}

	.select2-container .select2-selection__arrow:after, .select2-results__option {
		color: <?php echo esc_attr( $font_color ) ?>!important;
	}

	.select2-search__field {
		border: solid 1px <?php echo esc_attr( $font_color ) ?>!important;
	}

	.featherlight .geo-wrapper button, .featherlight .geo-wrapper .select2-dropdown .select2-results__option[aria-selected="true"],
	.featherlight .geo-wrapper .select2-dropdown .select2-results__option--highlighted {
		background-color: <?php echo esc_attr( $accent_color ) ?>;
	}

	.featherlight .geo-wrapper .select2-container--default .select2-selection--single, .featherlight .geo-wrapper input {
		border: solid 1px <?php echo esc_attr( $font_color ) ?>;
	}

	.featherlight .geo-wrapper input::-moz-placeholder {
		color: <?php echo esc_attr( $font_color ) ?>;
		opacity: 1;
	}

	.featherlight .geo-wrapper input:-ms-input-placeholder {
		color:  <?php echo esc_attr( $font_color ) ?>;
	}

	.featherlight .geo-wrapper input::-webkit-input-placeholder {
		color:  <?php echo esc_attr( $font_color ) ?>;
	}

</style>
