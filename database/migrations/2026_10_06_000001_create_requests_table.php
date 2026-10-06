<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            // Code de suivi remis à l'usager après le dépôt : il lui permet
            // de retrouver sa demande sans expose son NPI. Unique en base.
            $table->string('tracking_code', 16)->unique();
            // Le NPI est stocké en string pour préserver les zéros initiaux
            // (ex: "0123456789" ne doit jamais devenir 123456789).
            $table->string('npi', 10);
            $table->string('act_type');
            $table->unsignedTinyInteger('copies_count');
            $table->string('status')->default('submitted');
            // Motif de rejet : renseigné uniquement lors de la transition vers "rejected".
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // La consultation se fait par NPI, triée par date de création.
            $table->index(['npi', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
