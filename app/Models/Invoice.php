<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        "invoice_number",
        "medical_record_id",
        "patient_id",
        "subtotal",
        "discount_amount",
        "discount_percent",
        "tax_amount",
        "total_amount",
        "paid_amount",
        "change_amount",
        "payment_method",
        "payment_status",
        "payment_date",
        "notes",
        "cashier_id",
    ];

    protected $casts = [
        "subtotal" => "decimal:2",
        "discount_amount" => "decimal:2",
        "discount_percent" => "decimal:2",
        "tax_amount" => "decimal:2",
        "total_amount" => "decimal:2",
        "paid_amount" => "decimal:2",
        "change_amount" => "decimal:2",
        "payment_date" => "datetime",
    ];

    public const PAYMENT_METHOD_LABELS = [
        "cash" => "Tunai",
        "card" => "Kartu",
        "transfer" => "Transfer",
        "bpjs" => "BPJS",
        "insurance" => "Asuransi",
    ];

    public const PAYMENT_STATUS_LABELS = [
        "unpaid" => "Belum Bayar",
        "partial" => "Bayar Sebagian",
        "paid" => "Lunas",
    ];

    protected $appends = ["payment_method_label", "payment_status_label"];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, "cashier_id");
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        if (!$this->payment_method) {
            return "-";
        }
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ??
            $this->payment_method;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if (!$this->payment_status) {
            return "Belum Bayar";
        }
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ??
            $this->payment_status;
    }

    public static function generateInvoiceNumber(): string
    {
        $date = date("Ymd");
        $lastInvoice = self::whereDate("created_at", today())
            ->orderBy("id", "desc")
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("INV-%s-%04d", $date, $newNumber);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum("total_price");

        if ($this->discount_percent > 0) {
            $this->discount_amount =
                $this->subtotal * ($this->discount_percent / 100);
        }

        $this->total_amount =
            $this->subtotal - $this->discount_amount + $this->tax_amount;
    }

    public function scopeUnpaid($query)
    {
        return $query->where("payment_status", "unpaid");
    }

    public function scopePaid($query)
    {
        return $query->where("payment_status", "paid");
    }

    public function scopeToday($query)
    {
        return $query->whereDate("created_at", today());
    }
}
