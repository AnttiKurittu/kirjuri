(function () {
var directionality = (function () {
  'use strict';

  var PluginManager = tinymce.util.Tools.resolve('tinymce.PluginManager');

  var Tools = tinymce.util.Tools.resolve('tinymce.util.Tools');

  var setDir = function (editor, dir) {
    var dom = editor.dom;
    var curDir;
    var blocks = editor.selection.getSelectedBlocks();
    if (blocks.length) {
      curDir = dom.getAttrib(blocks[0], 'dir');
      Tools.each(blocks, function (block) {
        if (!dom.getParent(block.parentNode, '*[dir="' + dir + '"]', dom.getRoot())) {
          dom.setAttrib(block, 'dir', curDir !== dir ? dir : null);
        }
      });
      editor.nodeChanged();
    }
  };
  var $_30wzhdabje5nvboc = { setDir: setDir };

  var register = function (editor) {
    editor.addCommand('mceDirectionLTR', function () {
      $_30wzhdabje5nvboc.setDir(editor, 'ltr');
    });
    editor.addCommand('mceDirectionRTL', function () {
      $_30wzhdabje5nvboc.setDir(editor, 'rtl');
    });
  };
  var $_23jvpoaaje5nvbob = { register: register };

  var generateSelector = function (dir) {
    var selector = [];
    Tools.each('h1 h2 h3 h4 h5 h6 div p'.split(' '), function (name) {
      selector.push(name + '[dir=' + dir + ']');
    });
    return selector.join(',');
  };
  var register$1 = function (editor) {
    editor.addButton('ltr', {
      title: 'Left to right',
      cmd: 'mceDirectionLTR',
      stateSelector: generateSelector('ltr')
    });
    editor.addButton('rtl', {
      title: 'Right to left',
      cmd: 'mceDirectionRTL',
      stateSelector: generateSelector('rtl')
    });
  };
  var $_7sbdsvadje5nvboe = { register: register$1 };

  PluginManager.add('directionality', function (editor) {
    $_23jvpoaaje5nvbob.register(editor);
    $_7sbdsvadje5nvboe.register(editor);
  });
  function Plugin () {
  }

  return Plugin;

}());
})();
