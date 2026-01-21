CKEDITOR.dialog.add("mermaidDialog", function (editor) {
  return {
    title: "Schéma Mermaid",
    minWidth: 400,
    minHeight: 200,

    contents: [
      {
        id: "tab-basic",
        label: "Basic Settings",
        elements: [
          {
            type: "textarea",
            id: "mermaid",
            label:
              'Mermaid (<a href="https://mermaid-js.github.io/mermaid-live-editor/" target="_blank">Cliquer pour accéder à l\'éditeur</a>)',
            validate: CKEDITOR.dialog.validate.notEmpty(
              "Le champ Mermaid ne peut être vide."
            ),
          },
        ],
      },
    ],

    onOk: function () {
      var dialog = this;
      var content = dialog.getValueOf("tab-basic", "mermaid");

      // We need to format the content by replacing \n with <br /> html tag
      var container =
        '<div class="mermaid">' + content.replace(/\n/g, "<br />") + "</div>";

      editor.insertHtml(container);
    },
  };
});
