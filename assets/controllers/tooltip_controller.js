
import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import { renderTooltips } from '../scripts/tooltip';
import * as bootstrap from 'bootstrap'

export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element);

        this.component.on('render:finished', (component) => {
            renderTooltips();
            this.refreshScrollSpy();
        });
    }

    refreshScrollSpy()
    {
        const scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: this.scrollSpyTarget
        });
    }
}