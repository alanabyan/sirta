<script setup>
	import { onMounted, reactive, ref } from 'vue';
	import { useRouter } from 'vue-router';
	import { FileText, Plus, Pencil, Trash2, Eye } from 'lucide-vue-next';
	import api, { errorMessage } from '@/api';
	import { useAuth } from '@/stores/auth';
	import { useUi } from '@/stores/ui';
	import { useSave } from '@/composables/useSave';
	import PageHeader from '@/components/PageHeader.vue';
	import BaseModal from '@/components/BaseModal.vue';
	import FormField from '@/components/FormField.vue';
	import EmptyState from '@/components/EmptyState.vue';

	const auth = useAuth();
	const ui = useUi();
	const router = useRouter();
	const { saving, errors, save } = useSave('/template-surat');
	const items = ref([]);
	const loading = ref(true);
	const preview = ref(null);

	async function load() {
		try {
			items.value = (
				await api.get('/template-surat', { params: { all: 1 } })
			).data.data;
		} catch (e) {
			ui.error(errorMessage(e));
		} finally {
			loading.value = false;
		}
	}

	const kosong = () => ({
		id: null,
		kode: '',
		nama: '',
		deskripsi: '',
		isi: '',
	});
	const form = reactive(kosong());
	const showForm = ref(false);
	function buka(t) {
		Object.assign(
			form,
			kosong(),
			t ? { ...t, deskripsi: t.deskripsi ?? '' } : {},
		);
		errors.value = {};
		showForm.value = true;
	}
	async function simpan() {
		const { id, ...payload } = form;
		if (await save(id, payload)) {
			showForm.value = false;
			load();
		}
	}
	async function hapus(t) {
		if (
			!(await ui.ask({
				title: `Hapus template ${t.nama}?`,
				message: 'Surat yang sudah dibuat dari template ini tidak terpengaruh.',
				confirmText: 'Ya, hapus',
				danger: true,
			}))
		)
			return;
		try {
			await api.delete(`/template-surat/${t.id}`);
			ui.success('Template dihapus.');
			load();
		} catch (e) {
			ui.error(errorMessage(e));
		}
	}
	const pakai = (t) =>
		router.push({ path: '/surat-keluar', query: { baru: 1, template: t.id } });
	onMounted(load);
</script>

<template>
	<PageHeader
		title="Template Surat"
		description="Kerangka surat siap pakai agar format surat selalu seragam dan cepat dibuat."
	>
		<button
			v-if="auth.canDecide"
			class="btn btn-primary"
			@click="buka()"
		>
			<Plus :size="18" /> Template baru
		</button>
	</PageHeader>

	<div
		v-if="loading"
		class="loading"
	>
		<div class="spinner" />
	</div>
	<EmptyState
		v-else-if="!items.length"
		:icon="FileText"
		title="Belum ada template"
	/>
	<div
		v-else
		class="grid grid-3"
	>
		<article
			v-for="t in items"
			:key="t.id"
			class="card card-pad tpl"
		>
			<span class="ic"><FileText :size="22" /></span>
			<h3>{{ t.nama }}</h3>
			<p class="muted small grow">{{ t.deskripsi }}</p>
			<code>{{ t.kode }}</code>
			<div class="row row-wrap">
				<button
					class="btn btn-primary btn-sm"
					@click="pakai(t)"
				>
					Gunakan
				</button>
				<button
					class="btn btn-secondary btn-sm"
					@click="preview = t"
				>
					<Eye :size="14" /> Lihat
				</button>
				<template v-if="auth.canDecide">
					<button
						class="btn btn-icon"
						title="Ubah"
						@click="buka(t)"
					>
						<Pencil :size="16" />
					</button>
					<button
						class="btn btn-icon"
						title="Hapus"
						@click="hapus(t)"
					>
						<Trash2 :size="16" />
					</button>
				</template>
			</div>
		</article>
	</div>

	<BaseModal
		v-if="preview"
		:title="preview.nama"
		subtitle="Bagian {{…}} akan terisi otomatis."
		size="lg"
		hide-footer
		@close="preview = null"
	>
		<pre class="paper">{{ preview.isi }}</pre>
	</BaseModal>

	<BaseModal
		v-if="showForm"
		:title="form.id ? 'Ubah template' : 'Template baru'"
		size="lg"
		:saving="saving"
		@close="showForm = false"
		@save="simpan"
	>
		<div class="form-grid">
			<FormField
				label="Nama template"
				:error="errors.nama"
				required
				><input
					v-model="form.nama"
					class="input"
					required
			/></FormField>
			<FormField
				label="Kode"
				:error="errors.kode"
				hint="Unik, mis. SK-DOMISILI"
				required
				><input
					v-model="form.kode"
					class="input mono"
					required
			/></FormField>
			<FormField
				label="Deskripsi singkat"
				:error="errors.deskripsi"
				full
				><input
					v-model="form.deskripsi"
					class="input"
			/></FormField>
			<FormField
				label="Isi template"
				:error="errors.isi"
				full
				required
				hint="Baris 1–3 menjadi kop surat. Variabel: {{nomor}} {{tanggal}} {{nama}} {{nik}} {{alamat}} {{keperluan}}"
			>
				<textarea
					v-model="form.isi"
					class="textarea"
					style="
						min-height: 260px;
						font-family: ui-monospace, Consolas, monospace;
						font-size: 13px;
					"
					required
				/>
			</FormField>
		</div>
	</BaseModal>
</template>

<style scoped>
	.tpl {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.ic {
		width: 46px;
		height: 46px;
		border-radius: 14px;
		background: var(--primary-soft);
		color: var(--primary);
		display: grid;
		place-items: center;
	}
	h3 {
		font-size: 16px;
		margin-top: 6px;
	}
	code {
		font-size: 11.5px;
		color: var(--faint);
	}
	.paper {
		white-space: pre-wrap;
		font-family: 'Times New Roman', serif;
		font-size: 14px;
		line-height: 1.6;
		background: #fff;
		color: #000;
		border: 1px solid var(--line);
		border-radius: 10px;
		padding: 26px;
		margin: 0;
	}
</style>
