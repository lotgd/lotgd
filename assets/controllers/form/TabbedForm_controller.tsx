import { Controller } from '@hotwired/stimulus';
import { getComponent, Component } from '@symfony/ux-live-component';
import {renderTooltips} from "../../scripts/tooltip";

export default class extends Controller {
    static values = {
    };

    static targets = [
        "tab", "tabNav"
    ];

    declare hasTabTargets: boolean;
    declare tabTargets: HTMLElement[];

    declare hasTabNavTarget: boolean;
    declare tabNavTargets: HTMLElement[];

    initialize() {
    }

    toggle(event: PointerEvent) {
        const id = this.getTabId(event.target);

        this.tabTargets.forEach((tabTarget: HTMLElement) => {
            tabTarget.classList.remove("show");
        });

        this.tabNavTargets.forEach((tabNavTarget: HTMLElement) => {
            tabNavTarget.classList.remove("active");
        });

        this.element.querySelector(`#${id}`).classList.add("show");

        if (event.target instanceof HTMLElement) {
            event.target.parentElement.classList.add("active");
        }
    }

    getTabId(target: EventTarget|null) {
        if (target instanceof HTMLElement) {
            if (target.dataset.tabId) {
                return target.dataset.tabId;
            } else {
                return target.parentElement.dataset.tabId;
            }
        }

        return null;
    }
}
