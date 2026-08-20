
import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import * as d3 from 'd3';
import {Modal} from 'bootstrap';

export default class extends Controller {
    static values = {
    };

    static targets = [
        "modal"
    ];

    async initialize()
    {
        console.log(this.element);
        this.component = await getComponent(this.element);

        this.modal = new Modal(this.modalTarget);
    }

    onNewClick(event, data) {
        this.component.action('showForm', {}).then(() => this.modal.show());
    }
}
