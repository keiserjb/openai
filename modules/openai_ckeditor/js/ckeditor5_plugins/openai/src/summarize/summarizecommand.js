import { Command } from 'ckeditor5/src/core';

export default class SummarizeCommand extends Command {
    constructor(editor, config) {
        super(editor);
        this._config = config;
    }

    execute(options = {}) {
      const editor = this.editor;
      const selection = editor.model.document.selection;
      const range = selection.getFirstRange();
      let selectedText = null;

      for (const item of range.getItems()) {
        selectedText = item.data;
      }

      const prompt = 'Summarize the following text into something more compact, in the same language as the following: ' + selectedText;

      // @todo Need to have an AJAX indicator while the API waits for a response.
      // @todo add error handling

      editor.model.change(async writer => {
        const response = await fetch(drupalSettings.path.baseUrl + 'api/openai-ckeditor/completion', {
          method: 'POST',
          credentials: 'same-origin',
          body: JSON.stringify({'prompt': prompt, 'options': this._config}),
        });

        const reader = response.body.getReader();

        while (true) {
          const {value, done} = await reader.read();
          const text = new TextDecoder().decode(value);
          if (done) break;
          editor.model.insertContent(
            editor.model.insertContent( writer.createText(text) )
          );
        }
      } );
    }
}
