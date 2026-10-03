import { Controller } from '@hotwired/stimulus';
import { EditorView } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { linter, lintGutter } from '@codemirror/lint';
import { syntaxHighlighting, StreamLanguage } from '@codemirror/language';
import { classHighlighter } from '@lezer/highlight';

/**
 * @typedef {{type: "string"}} StringExpressionLimit
 * @typedef {{type: "int", min?: int, max?: int}} IntExpressionLimit
 * @typedef {StringExpressionLimit|IntExpressionLimit} ExpressionLimit
 */


export default class extends Controller {
    static targets = [
        "widget",
        "editor",
        "variableSelection"
    ];

    static values = {
        validVariables: { type: Array, default: [] },
        variableNamespace: { type: Object, default: {} }
    };

    connect() {
        // Reuse existing CodeMirror instance if it survived a Stimulus reconnection
        if (this.editorTarget.__codemirror) {
            this._syncFromFormElement();
            return;
        }

        this._buildEditor();
        this._observeFormElement();
        this._addVariableSelection();
    }

    disconnect() {
        this._formElementObserver?.disconnect();
        // Do NOT destroy editorView here — data-live-ignore keeps the DOM node alive,
        // so we want to reuse the instance on the next connect().
    }

    _getFormElement() {
        return this.widgetTarget.querySelector('input');
    }

    _buildEditor() {
        const formElement = this._getFormElement();
        const initialValue = formElement?.value ?? '';
        const self = this;

        const expressionLanguage = StreamLanguage.define({
            token(stream) {
                if (stream.eatSpace()) return null;
                if (stream.match(/^\d+(\.\d+)?/)) return 'number';
                if (stream.match(/^[+\-*/%()]/)) return 'operator';
                if (stream.match(/^[a-zA-Z_][a-zA-Z0-9_.]*/)) {
                    return self.validVariablesValue.includes(stream.current())
                        ? 'variableName'
                        : 'invalid';
                }
                stream.next();
                return 'invalid';
            },
        });

        const expressionLinter = linter((view) => {
            const diagnostics = [];
            const text = view.state.doc.toString();
            const identifierRegex = /[a-zA-Z_][a-zA-Z0-9_.]*/g;
            let match;
            while ((match = identifierRegex.exec(text)) !== null) {
                if (!self.validVariablesValue.includes(match[0])) {
                    diagnostics.push({
                        from: match.index,
                        to: match.index + match[0].length,
                        severity: 'error',
                        message: `Unknown variable: "${match[0]}"`,
                    });
                }
            }
            return diagnostics;
        });

        this.editorView = new EditorView({
            state: EditorState.create({
                doc: initialValue,
                extensions: [
                    expressionLanguage,
                    syntaxHighlighting(classHighlighter),
                    expressionLinter,
                    lintGutter(),
                    EditorView.updateListener.of((update) => {
                        if (update.docChanged && formElement) {
                            formElement.value = update.state.doc.toString();
                            formElement.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }),
                ],
            }),
            parent: this.editorTarget,
        });

        // Store on the DOM element so it survives Stimulus reconnects
        this.editorTarget.__codemirror = this.editorView;
    }

    _syncFromFormElement() {
        const formElement = this._getFormElement();
        const editorView = this.editorTarget.__codemirror;
        if (!formElement || !editorView) return;

        const serverValue = formElement.value;
        const editorValue = editorView.state.doc.toString();

        if (serverValue !== editorValue) {
            try {
                editorView.update({
                    changes: { from: 0, to: editorValue.length, insert: serverValue },
                });
            } catch (error) {
                // ignore errors
            }
        }
    }

    _observeFormElement() {
        const formElement = this._getFormElement();
        if (!formElement) return;

        // Watch for attribute/property changes the server might push to the formElement
        this._formElementObserver = new MutationObserver(() => {
            this._syncFromFormElement();
        });

        this._formElementObserver.observe(formElement, {
            attributes: true,
            attributeFilter: ['value'],
        });
    }

    /**
     * Adds options to the variableSelectionTarget, assuming its a select element
     * @private
     */
    _addVariableSelection() {
        // Do nothing if selection was not set
        if (!this.hasVariableSelectionTarget) {
            return;
        }

        console.log(this, this.hasVariableSelectionTarget, this.variableSelectionTarget);

        // Report error if target is not <select>
        if (this.variableSelectionTarget.tagName !== "select") {
            console.error("variableSelection target must <select>.");
        }

        // Reset content
        let option = null;
        const options = this.variableSelectionTarget;
        options.innerHTML = "";

        for (const [key, value] of Object.entries(this.variableNamespaceValue)) {
            if (!(value instanceof Object)) {
                continue;
            }

            if ("type" in value) {
                option = this._addVariableSelectionOption(key, value);
                options.appendChild(option);
            } else {
                let optGroup = document.createElement("optgroup");
                optGroup.label = key;

                for (const [subKey, subValue] of Object.entries(value)) {
                    if ("type" in subValue) {
                        option = this._addVariableSelectionOption(`${key}.${subKey}`, subValue);
                        optGroup.append(option)
                    }
                }

                options.appendChild(optGroup);
            }
        }
    }

    /**
     * Convert a namespace element with an ExpressionLimit array shape
     * @param {string} name
     * @param {ExpressionLimit} config
     * @returns {HTMLOptionElement}
     * @private
     */
    _addVariableSelectionOption(name, config) {
        const option = document.createElement("option");
        option.value = name;

        switch (config["type"]) {
            case "int":
                option.innerText = `${name} (${config["type"]}, min: ${config["min"]}, max: ${config["max"]})`;
                break;
            default:
                option.innerText = `${name} (${config["type"]})`;
        }

        return option;
    }

    /**
     * Inserts the selected variable (from variableSelectionTarget) at the current cursor position
     */
    insertVariable() {
        if (!this.hasVariableSelectionTarget) {
            return;
        }

        const options = this.variableSelectionTarget;
        const editorView = this.editorTarget.__codemirror;

        editorView.dispatch(editorView.state.replaceSelection(options.value));
    }
}
