import OpenAIUI from './openaiui';
import { Plugin } from 'ckeditor5/src/core';
import {ContextualBalloon} from 'ckeditor5/src/ui';

export default class OpenAI extends Plugin {
  static get requires() {
    return [OpenAIUI, ContextualBalloon];
  }
}
