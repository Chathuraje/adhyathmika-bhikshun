const { registerPlugin } = wp.plugins;
const { PluginSidebar } = wp.editPost;
const { PanelBody, TextControl, Button, Notice } = wp.components;
const { useState } = wp.element;

const ImagePromptSidebar = () => {
    const [prompt, setPrompt] = useState('');
    const [status, setStatus] = useState(null);

    const sendPrompt = () => {
        setStatus("loading");
        const formData = new FormData();
        formData.append('action', 'send_image_prompt');
        formData.append('prompt', prompt);
        formData.append('_ajax_nonce', IG_API.nonce);

        fetch(IG_API.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setStatus("success");
            } else {
                setStatus("error");
            }
        })
        .catch(() => setStatus("error"));
    };

    return (
        <PluginSidebar name="image-generator-sidebar" title="Image Generator">
            <PanelBody>
                <TextControl
                    label="Image Prompt"
                    value={prompt}
                    onChange={(val) => setPrompt(val)}
                />
                <Button isPrimary onClick={sendPrompt} disabled={status === "loading"}>
                    {status === "loading" ? "Sending..." : "Send to n8n"}
                </Button>
                {status === "success" && <Notice status="success" isDismissible>✅ Prompt sent!</Notice>}
                {status === "error" && <Notice status="error" isDismissible>❌ Something went wrong.</Notice>}
            </PanelBody>
        </PluginSidebar>
    );
};

registerPlugin("image-generator-plugin", {
    render: ImagePromptSidebar,
});
