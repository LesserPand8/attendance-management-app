<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'reason' => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
            'start_time.date_format' => '出勤時間は○○:○○の形式で入力してください',
            'end_time.date_format' => '退勤時間は○○:○○の形式で入力してください',
            '*.date_format' => '時間は○○:○○の形式で入力してください',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateWorkTimes($validator);
            $this->validateBreakTimes($validator);
        });
    }

    /**
     * 出勤・退勤時間のバリデーション
     */
    private function validateWorkTimes($validator)
    {
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        // 片方だけ入力されている場合のチェック
        if (($startTime && !$endTime) || (!$startTime && $endTime)) {
            $validator->errors()->add('work_time', '時間が入力されていません');
            return;
        }

        if ($startTime && $endTime) {
            try {
                $start = Carbon::createFromFormat('H:i', $startTime);
                $end = Carbon::createFromFormat('H:i', $endTime);

                // 1. 出勤時間が退勤時間より後、または退勤時間が出勤時間より前
                if ($start->greaterThanOrEqualTo($end)) {
                    $validator->errors()->add('work_time', '出勤時間もしくは退勤時間が不適切な値です');
                }
            } catch (\Exception $e) {
                $validator->errors()->add('work_time', '時間は○○:○○の形式で入力してください');
            }
        }
    }

    /**
     * 休憩時間のバリデーション
     */
    private function validateBreakTimes($validator)
    {
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        // 出勤・退勤時間がない場合は休憩時間の検証をスキップ
        if (!$startTime || !$endTime) {
            return;
        }

        try {
            $workStart = Carbon::createFromFormat('H:i', $startTime);
            $workEnd = Carbon::createFromFormat('H:i', $endTime);
        } catch (\Exception $e) {
            return; // 出勤・退勤時間が不正な場合はスキップ
        }

        // 既存の休憩時間をチェック
        $workId = $this->route('id');
        $breaks = DB::table('breakings')->where('work_id', $workId)->get();

        foreach ($breaks as $break) {
            $startInputName = 'break_start_' . $break->id;
            $endInputName = 'break_end_' . $break->id;

            $breakStart = $this->input($startInputName);
            $breakEnd = $this->input($endInputName);

            // 片方だけ入力されている場合
            if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                $validator->errors()->add('break_time', '時間が入力されていません');
                continue;
            }

            if ($breakStart && $breakEnd) {
                $this->validateSingleBreakTime($validator, $breakStart, $breakEnd, $workStart, $workEnd);
            }
        }

        // 新しい休憩時間をチェック
        for ($i = 1; $i <= 10; $i++) {
            $breakStart = $this->input("break_start_{$i}");
            $breakEnd = $this->input("break_end_{$i}");

            // 片方だけ入力されている場合
            if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                $validator->errors()->add('break_time', '時間が入力されていません');
                continue;
            }

            if ($breakStart && $breakEnd) {
                $this->validateSingleBreakTime($validator, $breakStart, $breakEnd, $workStart, $workEnd);
            }
        }
    }

    /**
     * 個別の休憩時間のバリデーション
     */
    private function validateSingleBreakTime($validator, $breakStart, $breakEnd, $workStart, $workEnd)
    {
        try {
            $breakStartTime = Carbon::createFromFormat('H:i', $breakStart);
            $breakEndTime = Carbon::createFromFormat('H:i', $breakEnd);

            // 2. 休憩開始時間が出勤時間より前、または退勤時間より後
            if ($breakStartTime->lessThan($workStart) || $breakStartTime->greaterThan($workEnd)) {
                $validator->errors()->add('break_time', '休憩時間が不適切な値です');
                return;
            }

            // 3. 休憩終了時間が退勤時間より後
            if ($breakEndTime->greaterThan($workEnd)) {
                $validator->errors()->add('break_time', '休憩時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 休憩開始時間が休憩終了時間より後
            if ($breakStartTime->greaterThanOrEqualTo($breakEndTime)) {
                $validator->errors()->add('break_time', '休憩時間が不適切な値です');
                return;
            }
        } catch (\Exception $e) {
            $validator->errors()->add('break_time', '時間は○○:○○の形式で入力してください');
        }
    }
}
