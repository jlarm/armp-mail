export type Block = {
    id: string;
    type:
        | 'text'
        | 'heading'
        | 'button'
        | 'image'
        | 'list'
        | 'code'
        | 'delimiter';
    data: Record<string, any>;
};

const escape = (value: unknown): string =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

const str = (value: unknown): string =>
    typeof value === 'string' ? value : '';

const renderBlock = (block: Block): string => {
    switch (block.type) {
        case 'heading': {
            const parsed = Number(block.data.level);
            const level = [1, 2, 3].includes(parsed) ? parsed : 2;
            const size = { 1: '28px', 2: '22px', 3: '18px' }[
                level as 1 | 2 | 3
            ];

            return `<h${level} style="margin:0 0 16px;font-size:${size};line-height:1.3;color:#0f172a;">${escape(block.data.text)}</h${level}>`;
        }
        case 'text':
            return `<div style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">${str(block.data.text)}</div>`;
        case 'button': {
            const url = escape(block.data.url || '#');

            return `<table cellpadding="0" cellspacing="0" style="margin:0 0 16px;"><tr><td style="background:#e4502b;border-radius:8px;"><a href="${url}" target="_blank" rel="noopener" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-weight:bold;">${escape(block.data.text || 'Button')}</a></td></tr></table>`;
        }
        case 'image':
            return str(block.data.url)
                ? `<img src="${escape(block.data.url)}" alt="${escape(block.data.alt)}" style="display:block;max-width:100%;margin:0 0 16px;border-radius:8px;" />`
                : '';
        case 'list': {
            const tag = block.data.style === 'ordered' ? 'ol' : 'ul';
            const items = str(block.data.text)
                .split('\n')
                .map((line) => line.trim())
                .filter((line) => line !== '')
                .map(
                    (line) =>
                        `<li style="margin:0 0 6px;">${escape(line)}</li>`,
                )
                .join('');

            return `<${tag} style="margin:0 0 16px;padding-left:20px;font-size:15px;line-height:1.6;color:#334155;">${items}</${tag}>`;
        }
        case 'code':
            return `<pre style="margin:0 0 16px;padding:16px;background:#0f172a;color:#e2e8f0;border-radius:8px;overflow:auto;font-size:13px;"><code>${escape(block.data.code)}</code></pre>`;
        case 'delimiter':
            return '<hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0;" />';
        default:
            return '';
    }
};

/**
 * Render the content blocks into the inner body HTML.
 */
export const renderBlocks = (blocks: Block[]): string =>
    blocks.map(renderBlock).join('\n');

/**
 * Render the blocks into a full, email-friendly HTML document.
 */
export const renderEmail = (blocks: Block[]): string =>
    `<!DOCTYPE html>
<html>
  <body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center" style="padding:24px 12px;">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;max-width:600px;">
          <tr><td style="padding:32px;">
${renderBlocks(blocks)}
          </td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>`;
