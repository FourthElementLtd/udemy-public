/// <reference path="../../../../../node_modules/@types/jquery/index.d.ts" />

interface JQuery {
	featherlight: any;
}

interface JQueryStatic {
	featherlight(content: JQuery|string, configuration?: JQueryFeatherlightConf): JQueryFeatherlight;
}

/**
 * Types generated following official documentation
 * https://github.com/noelboss/featherlight/
 */
interface JQueryFeatherlightConf {
	namespace?: string;
	targetAttr?: string;
	variant?: string;
	resetCss?: boolean;
	background?: HTMLElement;
	openTrigger?: string;
	closeTrigger?: string;
	filter?: string;
	root?: string;
	openSpeed?: number;
	closeSpeed?: number;
	closeOnClick?: string|boolean;
	closeOnEsc?: boolean;
	closeIcon?: string;
	loading?: string;
	persist?: boolean;
	otherClose?: string;
	beforeOpen?: Function;
	beforeContent?: Function;
	beforeClose?: Function;
	afterOpen?: Function;
	afterContent?: Function;
	afterClose?: Function;
	onKeyUp?: Function;
	onResize?: Function;
	type?: string;
	contentFilters?: string[];
}

interface JQueryFeatherlight {
	open(): void;
	close(): void;
}