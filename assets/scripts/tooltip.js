import * as bootstrap from 'bootstrap'

function renderTooltips() {

    const allowList = bootstrap.Tooltip.Default.allowList;
    allowList.dl = []
    allowList.dt = []
    allowList.dd = []

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
}

renderTooltips();

export { renderTooltips };