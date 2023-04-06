import { Command } from 'ckeditor5/src/core';
import FormView from './form';
import { ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';

export default class TranslateCommand extends Command {
    constructor(editor, config) {
        super(editor);
        this._balloon = this.editor.plugins.get( ContextualBalloon );
        this.formView = this._createFormView();
        this._config = config;
    }

    execute(options = {}) {
        this._showUI();
    }

    _createFormView() {
      const editor = this.editor;
      const formView = new FormView(editor.locale);


      this.listenTo( formView, 'submit', () => {
        const selection = editor.model.document.selection;
        const range = selection.getFirstRange();
        let selectedText = null;

        for (const item of range.getItems()) {
          selectedText = item.data; //return the selected text
        }

        const prompt = 'Translate the selected text into ' + formView.languageInputView.fieldView.element.value + ': ' + selectedText;

          // @todo Need to have an AJAX indicator while the API waits for a response.
          // @todo add error handling

          editor.model.change( writer => {
            fetch(drupalSettings.path.baseUrl + 'api/openai-ckeditor/completion', {
              method: 'POST',
              credentials: 'same-origin',
              body: JSON.stringify({'prompt': prompt, 'options': this._config}),
            })
              .then((response) => response.json())
              .then((answer) => editor.model.insertContent(
                editor.model.insertContent( writer.createText(answer.text), range )
              ))
              .then(() => this._hideUI()
            )
          } );
        } );

        // Hide the form view after clicking the "Cancel" button.
        this.listenTo(formView, 'cancel', () => {
          this._hideUI();
        } );

        // Hide the form view when clicking outside the balloon.
        clickOutsideHandler( {
          emitter: formView,
          activator: () => this._balloon.visibleView === formView,
          contextElements: [ this._balloon.view.element ],
          callback: () => this._hideUI()
        } );

        return formView;
      }

      _getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        let target = null;

        // Set a target position by converting view selection range to DOM.
        target = () => view.domConverter.viewRangeToDom(
          viewDocument.selection.getFirstRange()
        );

        return {
          target
        };
      }

      _showUI() {
        this._balloon.add( {
          view: this.formView,
          position: this._getBalloonPositionData()
        } );

        this.formView.focus();
      }

      _hideUI() {
        this.formView.languageInputView.fieldView.value = '';
        this.formView.element.reset();
        this._balloon.remove( this.formView );
        this.editor.editing.view.focus();
      }
}
