
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
        const dx = 60;
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

        const link = this.createLink(root, outerGroup);
        const node = this.createNode(root, outerGroup);

        const zoom = d3.zoom()
            .on("zoom", zoom => {d3.select("svg g").attr("transform", zoom.transform)})
        ;

        svg.call(zoom);
    }

    createNode(root, group)
    {
        const node = group.append("g")
            .attr("stroke-linejoin", "round")
            .attr("stroke-width", 3)
            .selectAll()
            .data(root.descendants())
            .join("g")
            .attr("transform", d => `translate(${d.y}, ${d.x})`)
        ;

        const sceneAdd = node.append("circle")
            .attr("data-lotgd-type", "scene-add")
            .attr("fill", "var(--bs-info)")
            .attr("r", 10)
        ;

        const sceneDel = node.append("circle")
            .attr("data-lotgd-type", "scene-delete")
            .attr("fill", "var(--bs-danger)")
            .attr("r", 10)
        ;

        const scene = node.append("circle")
            .attr("data-lotgd-type", "scene")
            .attr("fill", "var(--bs-secondary)")
            .attr("r", 15)
        ;

        node.append("text")
            .attr("dy", "-1.5em")
            .attr("x", 0)
            .attr("text-anchor", "middle")
            .text(d => d.data.title)
            .attr("stroke", "white")
            .attr("paint-order", "stroke");

        scene.on("click", this.onNodeClick.bind(this));
        sceneAdd.on("click", this.onNodeAddClick.bind(this));
        sceneDel.on("click", this.onNodeDelClick.bind(this));

        node.on("mouseover", (d, e) => {
            const circle = d.target.parentElement.querySelector("circle[data-lotgd-type='scene']");
            const addTool = d.target.parentElement.querySelector("circle[data-lotgd-type='scene-add']");
            const delTool = d.target.parentElement.querySelector("circle[data-lotgd-type='scene-delete']");
            const text = d.target.parentElement.querySelector("text");

            node.selectAll("g").sort((a, b) => (a.id != d.id) ? -1 : 1)

            d3.select(circle).transition().duration(100)
                .attr("fill", "var(--bs-primary)");

            d3.select(text).transition().duration(100)
                .style("font-weight", "bold");

            d3.select(addTool).transition().duration(100)
                .attr("transform", `translate(-15, 15)`)

            d3.select(delTool).transition().duration(100)
                .attr("transform", `translate(15, 15)`)
        })

        node.on("mouseout", (d, e) => {
            const circle = d.target.parentElement.querySelector("circle[data-lotgd-type='scene']");
            const addTool = d.target.parentElement.querySelector("circle[data-lotgd-type='scene-add']");
            const delTool = d.target.parentElement.querySelector("circle[data-lotgd-type='scene-delete']");
            const text = d.target.parentElement.querySelector("text");


            d3.select(circle).transition().duration(100)
                .attr("fill", "var(--bs-secondary)");

            d3.select(text).transition().duration(100)
                .style("font-weight", "normal");

            d3.select(addTool)
                .transition()
                .duration(50)
                .attr("transform", `translate(0, 0)`)

            d3.select(delTool)
                .transition()
                .duration(50)
                .attr("transform", `translate(0, 0)`)
        });

        return node;
    }

    createLink(root, outerGroup)
    {
        const links = outerGroup.append("g")
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

        return links;
    }

    onNodeClick(event, data)
    {
        this.component.action('showForm', {"scene": data.data.scene}).then(() => this.modal.show());
    }

    onNodeDelClick(event, data)
    {
        this.component.action('removeScene', {"scene": data.data.scene});
    }

    onNodeAddClick(event, data)
    {
        this.component.action('addScene', {"scene": data.data.scene}).then(() => this.modal.show());
    }
}