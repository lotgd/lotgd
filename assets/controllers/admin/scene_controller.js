
import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import * as d3 from 'd3';
import {Modal} from 'bootstrap';

export default class extends Controller {
    static values = {
        "tree": Object,
        "scenes": Array,
    };

    static targets = [
        "sceneView", "modal"
    ]

    async initialize()
    {
        this.component = await getComponent(this.element);
        this.component.on('render:finished', this.render.bind(this));

        this.modal = new Modal(this.modalTarget);
    }

    connect()
    {
        this.render();
    }

    render()
    {
        const width = 928;
        const marginTop = 10;
        const marginRight = 10;
        const marginBottom = 10;
        const marginLeft = 40;

        // Rows are separated by dx pixels, columns by dy pixels. These names can be counter-intuitive
        // (dx is a height, and dy a width). This because the tree must be viewed with the root at the
        // “bottom”, in the data domain. The width of a column is based on the tree’s height.
        const root = d3.hierarchy(this.treeValue);
        const dx = 40;
        const dy = 200; // (width - marginRight - marginLeft) / (1 + root.height);

        // Define the tree layout and the shape for links.
        const tree = d3.cluster().nodeSize([dx, dy]);
        root.sort((a, b) => d3.ascending(a.name, b.name));
        tree(root)

        let x0 = Infinity;
        let x1 = -x0;
        root.each(d => {
            if (d.x > x1) x1 = d.x;
            if (d.x < x0) x0 = d.x;
        })

        const height = x1 - x0 + dx * 2;

        // Create the SVG container, a layer for the links and a layer for the nodes.
        const svg = d3.select(this.sceneViewTarget).append("svg")
            .attr("width", width)
            .attr("height", height)
            .attr("viewBox", [-dy/3, x0 - dx, width, height])
            .attr("style", "max-width: 100%; height: auto; font: 10px sans-serif; user-select: none;");

        const outerGroup = svg.append("g");

        const link = outerGroup.append("g")
            .attr("fill", "none")
            .attr("stroke", "#555")
            .attr("stroke-opacity", 0.4)
            .attr("stroke-width", 1.5)
            .selectAll()
            .data(root.links())
            .join("path")
            .attr("d", d3.linkHorizontal()
                .x(d => d.y)
                .y(d => d.x)
            )
        ;

        const node = outerGroup.append("g")
            .attr("stroke-linejoin", "round")
            .attr("stroke-width", 3)
            .selectAll()
            .data(root.descendants())
            .join("g")
            .attr("transform", d => `translate(${d.y}, ${d.x})`)
        ;

        node.append("circle")
            .attr("fill", "var(--bs-secondary")
            .attr("r", 10)
        ;

        node.append("text")
            .attr("dy", "-1.5em")
            //.attr("x", d => d.children ? -6 : 6)
            //.attr("text-anchor", d => d.children ? "end" : "start")
            .attr("x", 0)
            .attr("text-anchor", "middle")
            .text(d => d.data.title)
            .attr("stroke", "white")
            .attr("paint-order", "stroke");

        const zoom = d3.zoom()
            .on("zoom", zoom => {d3.select("svg g").attr("transform", zoom.transform)})
        ;

        node.on("click", this.onNodeClick.bind(this));
        node.on("mouseover", (d, e) => {
            const circle = d.target.parentElement.querySelector("circle");
            const text = d.target.parentElement.querySelector("text");

            circle.style.fill = "var(--bs-primary)";
            text.style.fontWeight = "bold";
        })
        node.on("mouseout", (d, e) => {
            const circle = d.target.parentElement.querySelector("circle");
            const text = d.target.parentElement.querySelector("text");

            circle.style.fill = "var(--bs-secondary)";
            text.style.fontWeight = "normal";
        })

        svg.call(zoom);
    }

    onNodeClick(event, data)
    {
        console.log(data);

        this.component.action('showForm', {"scene": data.data.scene}).then(() => this.modal.show());
    }
}