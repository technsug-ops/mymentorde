<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFinanceEntry extends Model
{
    protected $table = 'company_finance_entries';

    protected $fillable = [
        'company_id', 'entry_date', 'type', 'category', 'title',
        'amount', 'currency', 'reference_no', 'notes',
        'source', 'bank_transaction_id', 'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'float',
    ];

    public static array $incomeCategories = [
        'student_fee'    => 'Öğrenci Ücreti',
        'commission_in'  => 'Komisyon Geliri',
        'consulting'     => 'Danışmanlık',
        'service'        => 'Hizmet Geliri',
        'other_income'   => 'Diğer Gelir',
    ];

    public static array $expenseCategories = [
        'salary'         => 'Maaş & Ödemeler',
        'rent'           => 'Kira & Ofis',
        'software'       => 'Yazılım & Lisans',
        'marketing'      => 'Pazarlama & Reklam',
        'travel'         => 'Seyahat & Konaklama',
        'tax'            => 'Vergi & SSK',
        'commission_out' => 'Ödenen Komisyon',
        'bank_fee'       => 'Banka Masrafı',
        'other_expense'  => 'Diğer Gider',
    ];

    public static function allCategories(): array
    {
        return array_merge(self::$incomeCategories, self::$expenseCategories);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
