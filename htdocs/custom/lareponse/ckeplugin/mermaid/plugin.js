CKEDITOR.plugins.add("mermaid", {
  icons: "mermaid",
  init: function (editor) {
    editor.addCommand(
      "insertMermaid",
      new CKEDITOR.dialogCommand("mermaidDialog")
    );
    CKEDITOR.dialog.add("mermaidDialog", this.path + "dialogs/mermaid.js");
    editor.ui.addButton("Mermaid", {
      label: "Ajouter un schéma Mermaid",
      command: "insertMermaid",
      toolbar: "insert,0",
    });
  },
});
