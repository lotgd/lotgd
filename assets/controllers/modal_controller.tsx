import { Controller } from '@hotwired/stimulus';
import { getComponent, Component } from '@symfony/ux-live-component';

export default class extends Controller {
    static values = {
    };

    static targets = [
        "modal"
    ];

    declare component: Component;
    declare modalTarget: HTMLDialogElement;
    declare hasModalTarget: boolean;

    async initialize()
    {
        if (this.element instanceof HTMLElement) {
            this.component = await getComponent(this.element);
            window.addEventListener('modal:close', () => this.modalTarget.close());
        }
    }

    onNewClick(event, data) {
        if (this.hasModalTarget) {
            this.component.action('showForm', {}).then(() => this.modalTarget.showModal());
        }
    }

    close(event: CloseEvent) {
        if (!(event.target instanceof HTMLElement)) {
            return;
        }

        const dialog = event.target.closest('dialog');
        dialog.close();
    }
}
