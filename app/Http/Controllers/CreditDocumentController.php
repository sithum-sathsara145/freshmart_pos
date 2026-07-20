<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\CloudinaryService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

/**
 * Signed-document evidence for credit sales. The cashier photographs the signed
 * credit bill either with the POS webcam (they are already authenticated) or with
 * their own phone via a QR code. The phone isn't logged in, so its upload URL is a
 * short-lived signed link and the page additionally demands a one-time code (shown
 * on the POS screen) or the cashier's own account password before it accepts the photo.
 */
class CreditDocumentController extends Controller
{
    private const TTL_MINUTES = 15;

    /**
     * Cashier asks for a phone-upload link: returns a signed URL, a QR of it, and a
     * one-time code (stored hashed, expiring). Called from the POS via AJAX.
     */
    public function link(Sale $sale)
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->codeKey($sale), Hash::make($code), now()->addMinutes(self::TTL_MINUTES));

        $url = URL::temporarySignedRoute('pos.credit.upload.form', now()->addMinutes(self::TTL_MINUTES), ['sale' => $sale->id]);

        return response()->json([
            'url'        => $url,
            'code'       => $code,
            'qr_svg'     => $this->qrSvg($url),
            'expires_in' => self::TTL_MINUTES * 60,
        ]);
    }

    /** Webcam upload straight from the POS computer (cashier already authenticated). */
    public function storeFromCounter(Request $request, Sale $sale)
    {
        $request->validate(['photo' => 'required|image|max:12288']);

        return response()->json(['success' => true, 'url' => $this->persist($sale, $request->file('photo'))]);
    }

    /** Poll target for the POS: has the signed copy landed yet? */
    public function status(Sale $sale)
    {
        return response()->json([
            'attached' => filled($sale->credit_doc_url),
            'url'      => $sale->credit_doc_url,
        ]);
    }

    /** Mobile upload page, opened from the QR (URL signature already validated by middleware). */
    public function phoneForm(Sale $sale)
    {
        return view('pos.credit_upload', [
            'sale'   => $sale,
            'action' => $this->signedStoreUrl($sale),
            'error'  => null,
        ]);
    }

    /** Handle the phone upload — gated by the one-time code OR the cashier's password. */
    public function storeFromPhone(Request $request, Sale $sale)
    {
        $request->validate([
            'photo'  => 'required|image|max:12288',
            'secret' => 'required|string',
        ]);

        if (! $this->secretOk($sale, $request->secret)) {
            $message = 'Wrong security code or password — use the code shown on the POS screen.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return view('pos.credit_upload', ['sale' => $sale, 'action' => $this->signedStoreUrl($sale), 'error' => $message]);
        }

        $this->persist($sale, $request->file('photo'));
        Cache::forget($this->codeKey($sale));

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return view('pos.credit_upload_done', ['sale' => $sale]);
    }

    // ── helpers ───────────────────────────────────────────────

    /** Upload the photo to Cloudinary and record it against the sale. Returns the URL. */
    private function persist(Sale $sale, $file): string
    {
        $res = app(CloudinaryService::class)->upload($file->getRealPath(), 'credit-documents');

        $sale->update([
            'credit_doc_url'         => $res['url'],
            'credit_doc_public_id'   => $res['public_id'] ?? null,
            'credit_doc_uploaded_at' => now(),
        ]);

        return $res['url'];
    }

    /** Accept either the one-time code shown on the POS, or the selling cashier's password. */
    private function secretOk(Sale $sale, string $secret): bool
    {
        $hashed = Cache::get($this->codeKey($sale));
        if ($hashed && Hash::check($secret, $hashed)) {
            return true;
        }

        $user = $sale->user;
        return $user && $user->password && Hash::check($secret, $user->password);
    }

    private function signedStoreUrl(Sale $sale): string
    {
        return URL::temporarySignedRoute('pos.credit.upload.store', now()->addMinutes(self::TTL_MINUTES), ['sale' => $sale->id]);
    }

    private function codeKey(Sale $sale): string
    {
        return "credit_upload_code:{$sale->id}";
    }

    /** Render the link as an inline SVG QR (no XML prolog, so it embeds in HTML). */
    private function qrSvg(string $data): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($data);

        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
    }
}
